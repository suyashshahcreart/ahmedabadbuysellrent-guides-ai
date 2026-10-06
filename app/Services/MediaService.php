<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaService
{
    public function storeUploaded($file, ?string $alt = null): \App\Models\MediaAsset
    {
        $name = uniqid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '-', $file->getClientOriginalName());
        $path = $file->storeAs('media/'.date('Y/m'), $name, 'public');
        $full = Storage::disk('public')->path($path);

        $width = $height = null;
        $webpPath = null;
        if (str_starts_with((string) $file->getMimeType(), 'image/')) {
            try {
                $info = getimagesize($full);
                $width = $info[0] ?? null;
                $height = $info[1] ?? null;
                $manager = new ImageManager(new Driver());
                $image = $manager->read($full);
                $webpRel = preg_replace('/\.[^.]+$/', '.webp', $path);
                $image->toWebp(82)->save(Storage::disk('public')->path($webpRel));
                $webpPath = $webpRel;
            } catch (\Throwable $e) {
                // keep original
            }
        }

        return \App\Models\MediaAsset::create([
            'disk' => 'public',
            'path' => $path,
            'webp_path' => $webpPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize() ?: 0,
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
        ]);
    }
}
