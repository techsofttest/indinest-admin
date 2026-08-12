<?php

namespace App\Traits;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait ProcessesImageUploads
{
    /**
     * Compress uploaded image, convert to WebP, and save to public disk.
     */
    public static function convertToWebpAndCompress(TemporaryUploadedFile $file, string $directory = 'uploads'): string
    {
        $filename = Str::random(40) . '.webp';
        $tempPath = $file->getRealPath();
        
        $imageInfo = @getimagesize($tempPath);
        if ($imageInfo) {
            $mime = $imageInfo['mime'];
            switch ($mime) {
                case 'image/jpeg':
                case 'image/jpg':
                    $image = @imagecreatefromjpeg($tempPath);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($tempPath);
                    break;
                case 'image/webp':
                    $image = @imagecreatefromwebp($tempPath);
                    break;
                case 'image/gif':
                    $image = @imagecreatefromgif($tempPath);
                    break;
                default:
                    $image = false;
            }
            
            if ($image) {
                // Preserve transparency
                imagealphablending($image, false);
                imagesavealpha($image, true);
                
                $tempWebpPath = tempnam(sys_get_temp_dir(), 'webp');
                if (@imagewebp($image, $tempWebpPath, 80)) {
                    imagedestroy($image);
                    
                    $path = Storage::disk('public')->putFileAs($directory, new \Illuminate\Http\File($tempWebpPath), $filename);
                    @unlink($tempWebpPath);
                    return $path;
                }
                
                imagedestroy($image);
            }
        }
        
        // Fallback: save original
        return $file->storeAs($directory, Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
    }
}
