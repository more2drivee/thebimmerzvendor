<?php

namespace Modules\Connector\Http\Controllers\Api;



use App\Media;
use FFMpeg\FFMpeg;
use App\Restaurant\Booking;
use Modules\Repair\Entities\JobSheet;
use Illuminate\Http\Request;

use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\Dimension;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{


    public function storeMedia(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;
    
   
    
        try {
            // Filter request data
            $filteredData = $request->only(['booking_id', 'images']);
       
            
            // Filter out empty/invalid files before validation
            if (isset($filteredData['images']) && is_array($filteredData['images'])) {
                $filteredData['images'] = array_filter($filteredData['images'], function($file) {
                    $isValid = $file && $file instanceof \Illuminate\Http\UploadedFile && $file->isValid();
                    Log::info('Filtering file', [
                        'file_object' => $file,
                        'is_uploaded_file' => $file instanceof \Illuminate\Http\UploadedFile,
                        'is_valid' => $isValid,
                        'file_getClientOriginalName' => $file instanceof \Illuminate\Http\UploadedFile ? $file->getClientOriginalName() : 'not_a_file',
                        'file_getPathname' => $file instanceof \Illuminate\Http\UploadedFile ? $file->getPathname() : 'not_a_file',
                        'file_getSize' => $file instanceof \Illuminate\Http\UploadedFile ? $file->getSize() : 'not_a_file'
                    ]);
                    return $isValid;
                });
            }
            
        
            // Validate input
            $validator = Validator::make($filteredData, [
                'booking_id' => 'required|integer|exists:bookings,id',
                'images' => 'nullable|array',
                'images.*' => 'sometimes|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi,flv,webp,webm|max:51200',
            ]);

       

            if ($validator->fails()) {
               
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            // Fetch booking details
            $booking = Booking::find($filteredData['booking_id']);
            if (!$booking) {
                Log::error('Booking not found', ['booking_id' => $filteredData['booking_id']]);
                return response()->json(['message' => 'Booking not found.'], 404);
            }
          
            DB::beginTransaction();
    
            // Handle Media Upload (Ensure correct model_type and model_id)
            if ($request->hasFile('images')) {
               
                foreach ($request->file('images') as $index => $file) {
                   
                    
                    try {
                        // Skip empty files
                        if (!$file || !$file->isValid() || $file->getSize() === 0) {
                            Log::warning('Skipping empty or invalid file', [
                                'file_name' => $file ? $file->getClientOriginalName() : 'null',
                                'is_valid' => $file ? $file->isValid() : false,
                                'size' => $file ? $file->getSize() : 0,
                                'error' => $file ? $file->getError() : 'no_file'
                            ]);
                            continue;
                        }

                        // Compress and store the image or video
                        $filePath = $this->handleFileCompression($file, $booking->id, $business_id);
                        
                        if (!$filePath) {
                            Log::error('File compression failed', [
                                'file_name' => $file->getClientOriginalName(),
                                'model_id' => $booking->id,
                                'business_id' => $business_id,
                            ]);
                            continue; // Skip to the next file if compression failed
                        }
    
                      
                        // Create a new media record
                        $media = Media::create([
                            'business_id' => $business_id,
                            'file_name' => $filePath,
                            'uploaded_by' => auth()->id(),
                            'model_id' => $booking->id,
                            'model_type' => Booking::class,
                        ]);
                      
                    } catch (\Exception $e) {
                        Log::error('Error while processing media for booking', [
                            'error_message' => $e->getMessage(),
                            'error_trace' => $e->getTraceAsString(),
                            'file_name' => $file->getClientOriginalName(),
                            'model_id' => $booking->id,
                        ]);
                    }
                }
            } else {
                Log::warning('No files found in the request', [
                    'request_all' => $request->all(),
                    'hasFile_images' => $request->hasFile('images'),
                    'input_images' => $request->input('images')
                ]);
            }
    
            DB::commit();
    
            Log::info('Media upload process completed successfully');
    
            return response()->json([
                'message' => 'Media uploaded successfully for booking',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in media upload', ['exception' => $e->getMessage()]);
    
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


public function compressVideo($file, $path, $quality = 20) // Default CRF value is set to 20
{
    try {
        // Get the original file path
        $inputFile = $file->getRealPath();
        
        // Define the output path
        $outputPath = storage_path('app/public/' . $path);
        
        // Ensure the directory exists
        $directory = dirname($outputPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Get FFmpeg/FFProbe paths from environment or use system defaults
        $ffmpegPath = env('FFMPEG_PATH', '/usr/bin/ffmpeg');
        $ffprobePath = env('FFPROBE_PATH', '/usr/bin/ffprobe');

        // Check if FFmpeg binaries exist; if not, store video without compression
        if (!file_exists($ffmpegPath) || !file_exists($ffprobePath)) {
            Log::warning('FFmpeg binaries not found, storing video without compression', [
                'ffmpeg_path' => $ffmpegPath,
                'ffprobe_path' => $ffprobePath,
            ]);
            return $this->storeVideoWithoutCompression($file, $path);
        }

        // Specify FFmpeg and FFProbe binaries explicitly
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => $ffmpegPath,
            'ffprobe.binaries' => $ffprobePath,
        ]);

        // Open the video file
        $video = $ffmpeg->open($inputFile);

        // Resize the video (optional - you can adjust or remove this based on your needs)
        $video->filters()->resize(new Dimension(1280, 720)); // Increase resolution to 720p for better quality

        // Create the X264 format instance with the correct codecs:
        $format = new X264('libmp3lame', 'libx264');

        // Adjust quality by setting CRF based on the quality parameter
        $format->setAdditionalParameters([
            '-crf', $quality,           // CRF to control the quality (lower = better quality)
            '-preset', 'slow',          // Use slow preset for better compression efficiency (change this to 'medium' if you want faster processing)
            '-profile:v', 'high',       // Set the profile to 'high' for better compression and quality
            '-level', '4.0',            // Set video level for compatibility (you can change based on the target device)
            '-b:v', '1500k',            // Set a bitrate to control the video size and quality
        ]);

        // Save the compressed video to the output path
        $video->save($format, $outputPath);

        // Get the final size after processing
        $finalSize = filesize($outputPath);

        Log::info('Video file processed', [
            'final_size' => $finalSize,
            'file_name' => $file->getClientOriginalName()
        ]);

        return $path;

    } catch (\Exception $e) {
        Log::error('Error processing video file', [
            'error' => $e->getMessage(),
            'file_name' => $file->getClientOriginalName()
        ]);
        
        // Fallback: store video without compression if FFmpeg fails
        Log::info('Falling back to storing video without compression');
        return $this->storeVideoWithoutCompression($file, $path);
    }
}

/**
 * Store video file without compression (fallback when FFmpeg is unavailable)
 */
public function storeVideoWithoutCompression($file, $path)
{
    try {
        $outputPath = storage_path('app/public/' . $path);
        
        // Ensure the directory exists
        $directory = dirname($outputPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Move the file to storage
        $file->move($directory, basename($outputPath));

        Log::info('Video stored without compression', [
            'file_name' => $file->getClientOriginalName(),
            'path' => $path
        ]);

        return $path;
    } catch (\Exception $e) {
        Log::error('Failed to store video without compression', [
            'error' => $e->getMessage(),
            'file_name' => $file->getClientOriginalName()
        ]);
        return null;
    }
}

// Modified handleFileCompression to include validation - NO COMPRESSION, JUST STORE AS-IS
public function handleFileCompression($file, $jobSheetId, $businessId)
{
    // Generate a unique file name
    $fileName = time() . '_' . $file->getClientOriginalName();
    $path = "booking/{$jobSheetId}/{$fileName}";

    // Get file MIME type
    $mimeType = $file->getMimeType();
    
    Log::info('handleFileCompression called', [
        'file_name' => $file->getClientOriginalName(),
        'mime_type' => $mimeType,
        'path' => $path,
        'jobSheetId' => $jobSheetId,
        'businessId' => $businessId
    ]);

    try {
        if (str_starts_with($mimeType, 'image')) {
            Log::info('Processing as image', ['mime_type' => $mimeType]);
            return $this->compressImage($file, $path);
        } else if (str_starts_with($mimeType, 'video')) {
            Log::info('Processing as video - storing without compression', ['mime_type' => $mimeType]);
            // Store video as-is without compression
            return $this->storeVideoWithoutCompression($file, $path);
        } else if (str_starts_with($mimeType, 'application/pdf') || 
                  str_starts_with($mimeType, 'application/msword') || 
                  str_starts_with($mimeType, 'application/vnd.ms-excel')) {
            Log::info('Processing as document', ['mime_type' => $mimeType]);
            return $this->storeDocument($file, $path);
        } else {
            Log::warning('Unknown file type, attempting to store as-is', ['mime_type' => $mimeType]);
            return $this->storeDocument($file, $path);
        }
    } catch (\Exception $e) {
        Log::error('File processing error', [
            'error' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString(),
            'file_type' => $mimeType,
            'file_name' => $file->getClientOriginalName()
        ]);
        return null;
    }

    return null;
}




// Helper function to validate video files
public function validateVideo($file)
{
    // List of allowed video MIME types
    $allowedTypes = [
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-flv',
        'video/webm'
    ];

    // Maximum file size (50MB for initial upload)
    $maxUploadSize = 50 * 1024 * 1024;

    // Check file size
    if ($file->getSize() > $maxUploadSize) {
        throw new \Exception('File size exceeds maximum limit of 50MB');
    }

    // Check file type
    if (!in_array($file->getMimeType(), $allowedTypes)) {
        throw new \Exception('Invalid video format. Allowed formats: MP4, MOV, AVI, FLV, WEBM');
    }

    return true;
}


public function storeDocument($file, $path)
{
    // Get the file's original name and store it at the specified path
    $outputPath = storage_path('app/public/' . $path);

    // Ensure the directory exists
    $directory = dirname($outputPath);
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);  // Create the directory if it doesn't exist
    }

    // Move the document file to the designated path
    $file->move($directory, basename($outputPath));

    return $outputPath;
}

public function compressImage($imagePath, $savePath)
{
    // Load the image
    $image = imagecreatefromstring(file_get_contents($imagePath));

    // Check if the image was created successfully
    if (!$image) {
        throw new \Exception("Could not create image from file.");
    }

    // Set the desired quality (0-100)
    $quality = 30;

    // Define full storage path
    $fullPath = storage_path('app/public/' . $savePath);

    // Ensure the directory exists
    $directory = dirname($fullPath);
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }

    // Save the compressed image as JPEG
    if (imagejpeg($image, $fullPath, $quality)) {
        // Free up memory
        imagedestroy($image);
        return $savePath; // Return the relative path
    } else {
        throw new \Exception("Failed to save compressed image.");
    }
}

    public function deleteMedia(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;
    
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'media_id' => 'required|integer|exists:media,id',
            ]);
    
            if ($validator->fails()) {
                Log::error('Validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            // Find the media record
            $media = Media::where('id', $request->media_id)
                ->where('business_id', $business_id)
                ->first();
    
            if (!$media) {
                Log::error('Media not found', [
                    'media_id' => $request->media_id,
                    'business_id' => $business_id
                ]);
                return response()->json(['message' => 'Media not found.'], 404);
            }
    
            DB::beginTransaction();
    
            try {
                // Delete the physical file from storage
                $filePath = storage_path('app/public/' . $media->file_name);
                if (file_exists($filePath)) {
                    unlink($filePath);
                    Log::info('Physical file deleted', ['file_path' => $filePath]);
                } else {
                    Log::warning('Physical file not found', ['file_path' => $filePath]);
                }
    
                // Delete the media record from database
                $media->delete();
                Log::info('Media record deleted from database', ['media_id' => $media->id]);
    
                DB::commit();
    
                Log::info('Media deletion completed successfully', [
                    'media_id' => $request->media_id
                ]);
    
                return response()->json([
                    'message' => 'Media deleted successfully',
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error during media deletion', ['exception' => $e->getMessage()]);
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error in media deletion', ['exception' => $e->getMessage()]);
    
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}

