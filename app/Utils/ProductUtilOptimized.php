<?php

namespace App\Utils;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ProductUtilOptimized extends ProductUtil
{
    /**
     * Optimized version of uploadFile method that processes images more efficiently
     *
     * @param  obj  $request, string $file_name, string dir_name
     * @return string
     */
    public function uploadFile($request, $file_name, $dir_name, $file_type = 'document')
    {
        //If app environment is demo return null
        if (config('app.env') == 'demo') {
            return null;
        }

        $uploaded_file_name = null;
        if ($request->hasFile($file_name) && $request->file($file_name)->isValid()) {
            $file = $request->file($file_name);
            
            //Check if mime type is image
            if ($file_type == 'image') {
                if (strpos($file->getClientMimeType(), 'image/') === false) {
                    throw new \Exception('Invalid image file');
                }
                
                // Process image with Intervention Image for optimization
                try {
                    // Create image instance
                    $image = Image::make($file);
                    
                    // Resize if too large (max dimension 1200px)
                    $maxDimension = 1200;
                    if ($image->width() > $maxDimension || $image->height() > $maxDimension) {
                        $image->resize($maxDimension, $maxDimension, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }
                    
                    // Optimize quality
                    $image->encode($image->extension, 80);
                    
                    // Generate filename
                    $new_file_name = time() . '_' . $file->getClientOriginalName();
                    
                    // Save the optimized image
                    $image->save(public_path($dir_name) . '/' . $new_file_name);
                    
                    $uploaded_file_name = $new_file_name;
                } catch (\Exception $e) {
                    // Fallback to regular upload if image processing fails
                    if ($file->getSize() <= config('constants.document_size_limit')) {
                        $new_file_name = time() . '_' . $file->getClientOriginalName();
                        if ($file->storeAs($dir_name, $new_file_name)) {
                            $uploaded_file_name = $new_file_name;
                        }
                    }
                }
            } else if ($file_type == 'document') {
                // For documents, use regular upload
                if (!in_array($file->getClientMimeType(), array_keys(config('constants.document_upload_mimes_types')))) {
                    throw new \Exception('Invalid document file');
                }
                
                if ($file->getSize() <= config('constants.document_size_limit')) {
                    $new_file_name = time() . '_' . $file->getClientOriginalName();
                    if ($file->storeAs($dir_name, $new_file_name)) {
                        $uploaded_file_name = $new_file_name;
                    }
                }
            }
        }

        return $uploaded_file_name;
    }
}
