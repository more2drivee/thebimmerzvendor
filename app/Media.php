<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $appends = ['display_name', 'display_url'];

    /**
     * Get all of the owning mediable models.
     */
    // public function mediable()
    // {
    //     return $this->morphTo();
    // }
    public function mediable()
    {
        return $this->morphTo();
    }

    /**
     * Get display name for the media
     */
    public function getDisplayNameAttribute()
    {
        $array = explode('_', $this->file_name, 3);

        return ! empty($array[2]) ? $array[2] : $array[1];
    }

    /**
     * Get display link for the media
     */
    public function getDisplayUrlAttribute()
    {
        // Check if file is stored in the new storage location (job_sheets, etc.)
        if (strpos($this->file_name, '/') !== false) {
            // File path includes directory structure (e.g., job_sheets/1/image.jpg) or media/filename
            // Prefer files stored in storage/app/public
            $publicPath = storage_path('app/public/' . $this->file_name);

            // If file is missing from public disk but exists in the old location (storage/app/),
            // copy it over so that the symlinked public/storage path works correctly.
            if (!file_exists($publicPath)) {
                $oldPath = storage_path('app/' . $this->file_name);
                if (file_exists($oldPath)) {
                    // ensure directory exists in public disk
                    $dir = dirname($this->file_name);
                    if (!empty($dir) && !Storage::disk('public')->exists($dir)) {
                        Storage::disk('public')->makeDirectory($dir);
                    }
                    Storage::disk('public')->put($this->file_name, file_get_contents($oldPath));
                }
            }

            $path = asset('storage/' . $this->file_name);
        } else {
            // Check if file exists in storage/app/public/media/ first
            $storagePath = storage_path('app/public/media/' . $this->file_name);
            if (file_exists($storagePath)) {
                $path = asset('storage/media/' . rawurlencode($this->file_name));
            } else {
                // maybe the file was stored in storage/app/media by the old upload routine
                $oldPath = storage_path('app/media/' . $this->file_name);
                if (file_exists($oldPath)) {
                    // copy to public disk so asset link works
                    Storage::disk('public')->put('media/'.$this->file_name, file_get_contents($oldPath));
                    $path = asset('storage/media/' . rawurlencode($this->file_name));
                } else {
                    // Legacy path for files in public/uploads/media
                    $path = asset('/uploads/media/'.rawurlencode($this->file_name));
                }
            }
        }

        return $path;
    }

    /**
     * Get display path for the media
     */
    public function getDisplayPathAttribute()
    {
        // Check if file is stored in the new storage location (job_sheets, etc.)
        if (strpos($this->file_name, '/') !== false) {
            // File path includes directory structure (e.g., job_sheets/1/image.jpg)
            // Use storage path for files in storage/app/public
            $path = storage_path('app/public/' . $this->file_name);
        } else {
            // Legacy path for files in public/uploads/media
            $path = public_path('uploads/media').'/'.rawurlencode($this->file_name);
        }

        return $path;
    }

    /**
     * Get display link for the media
     */
    public function thumbnail($size = [60, 60], $class = null)
    {
        $html = '<img';
        $html .= ' src="'.$this->display_url.'"';
        $html .= ' width="'.$size[0].'"';
        $html .= ' height="'.$size[1].'"';

        if (! empty($class)) {
            $html .= ' class="'.$class.'"';
        }

        $html .= '>';

        return $html;
    }

    /**
     * Uploads files from the request and add's medias to the supplied model.
     *
     * @param  int  $business_id, obj $model, $obj $request, string $file_name
     */
    public static function uploadMedia($business_id, $model, $request, $file_name, $is_single = false, $model_media_type = null)
    {
        //If app environment is demo return null
        if (config('app.env') == 'demo') {
            return null;
        }

        $uploaded_files = [];

        if ($request->hasFile($file_name)) {
            $files = $request->file($file_name);

            //If multiple files present
            if (is_array($files)) {
                foreach ($files as $file) {
                    $uploaded_file = Media::uploadFile($file, $model);

                    if (! empty($uploaded_file)) {
                        $uploaded_files[] = $uploaded_file;
                    }
                }
            } else {
                $uploaded_file = Media::uploadFile($files, $model);
                if (! empty($uploaded_file)) {
                    $uploaded_files[] = $uploaded_file;
                }
            }
        }

        //check if base64
        if (! empty($request->$file_name) && ! is_array($request->$file_name)) {
            $base64_array = explode(',', $request->$file_name);

            $base64_string = $base64_array[1] ?? $base64_array[0];

            if (Media::is_base64($base64_string)) {
                $uploaded_files[] = Media::uploadBase64Image($base64_string);
            }
        }

        if (! empty($uploaded_files)) {
            //If one to one relationship upload single file
            if ($is_single) {
                $uploaded_files = $uploaded_files[0];
            }
            
            // For JobSheet models, use direct Media::create like the API
            if ($model && get_class($model) === 'Modules\Repair\Entities\JobSheet') {
                if (is_array($uploaded_files)) {
                    foreach ($uploaded_files as $filePath) {
                        Media::create([
                            'business_id' => $business_id,
                            'file_name' => $filePath,
                            'uploaded_by' => auth()->id(),
                            'model_id' => $model->id,
                            'model_type' => get_class($model),
                        ]);
                    }
                } else {
                    Media::create([
                        'business_id' => $business_id,
                        'file_name' => $uploaded_files,
                        'uploaded_by' => auth()->id(),
                        'model_id' => $model->id,
                        'model_type' => get_class($model),
                    ]);
                }
            } else {
                // Use legacy approach for other models
                Media::attachMediaToModel($model, $business_id, $uploaded_files, $request, $model_media_type);
            }
        }
    }

    public static function is_base64($s)
    {
        return (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s);
    }

    /**
     * Uploads requested file to storage.
     */
    public static function uploadFile($file, $model = null)
    {
        $file_name = null;
        
        // If model is provided and it's a JobSheet, use the exact same approach as API
        if ($model && get_class($model) === 'Modules\Repair\Entities\JobSheet') {
            // Use exact same filename pattern as API: time() . '_' . original name
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = "job_sheets/{$model->id}/{$fileName}";

            // Store file in public disk exactly like API
            Storage::disk('public')->putFileAs("job_sheets/{$model->id}", $file, $fileName);
            
            $file_name = $filePath;
        } else {
            // Legacy approach for other models
            $new_file_name = time().'_'.mt_rand().'_'.$file->getClientOriginalName();
            // store using public disk so files are accessible via the public/storage symlink
            if (Storage::disk('public')->putFileAs('media', $file, $new_file_name)) {
                $file_name = 'media/' . $new_file_name;
            }
        }

        return $file_name;
    }

    /**
     * Upload file for JobSheet with proper path structure
     */
    public static function uploadFileForJobSheet($file, $jobSheetId)
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = "job_sheets/{$jobSheetId}/{$fileName}";

        // Store file in public disk
        Storage::disk('public')->putFileAs("job_sheets/{$jobSheetId}", $file, $fileName);
        
        return $filePath;
    }

    public static function uploadBase64Image($base64_string, $subfolder = null)
    {
        $file_name = time().'_'.mt_rand().'_media.jpg';

        // Use storage if subfolder provided (job_sheets, booking, etc.)
        if (!empty($subfolder)) {
            $filePath = "{$subfolder}/{$file_name}";
            Storage::disk('public')->put($filePath, base64_decode($base64_string));
            return $filePath;
        }

        // Legacy: save to public/uploads/media/
        $output_file = public_path('uploads').'/media/'.$file_name;

        // open the output file for writing
        $ifp = fopen($output_file, 'wb');

        fwrite($ifp, base64_decode($base64_string));

        // clean up the file resource
        fclose($ifp);

        return $file_name;
    }

    /**
     * Deletes resource from database and storage
     */
    public static function deleteMedia($business_id, $media_id)
    {
        $media = Media::where('business_id', $business_id)
                        ->findOrFail($media_id);

        // Check if file is in new storage location or legacy location
        if (strpos($media->file_name, '/') !== false) {
            // New storage location (storage/app/public/)
            Storage::disk('public')->delete($media->file_name);
        } else {
            // Legacy location (public/uploads/media/)
            $media_path = public_path('uploads/media/'.$media->file_name);
            if (file_exists($media_path)) {
                unlink($media_path);
            }
        }
        
        $media->delete();
    }

    public function uploaded_by_user()
    {
        return $this->belongsTo(\App\User::class, 'uploaded_by');
    }

    public static function attachMediaToModel($model, $business_id, $uploaded_files, $request = null, $model_media_type = null)
    {
        if (! empty($uploaded_files)) {
            if (is_array($uploaded_files)) {
                $media_obj = [];
                foreach ($uploaded_files as $value) {
                    $media_obj[] = new \App\Media([
                        'file_name' => $value,
                        'business_id' => $business_id,
                        'description' => ! empty($request->description) ? $request->description : null,
                        'uploaded_by' => ! empty($request->uploaded_by) ? $request->uploaded_by : auth()->user()->id,
                        'model_media_type' => $model_media_type,
                    ]);
                }

                $model->media()->saveMany($media_obj);
            } else {
                //delete previous media if exists
                $model->media()->delete();

                $media_obj = new \App\Media([
                    'file_name' => $uploaded_files,
                    'business_id' => $business_id,
                    'description' => ! empty($request->description) ? $request->description : null,
                    'uploaded_by' => ! empty($request->uploaded_by) ? $request->uploaded_by : auth()->user()->id,
                    'model_media_type' => $model_media_type,
                ]);
                $model->media()->save($media_obj);
            }
        }
    }
}
