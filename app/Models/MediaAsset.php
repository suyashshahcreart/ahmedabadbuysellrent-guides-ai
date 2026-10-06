<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    protected $fillable = [
        'disk', 'path', 'webp_path', 'original_name', 'mime_type', 'size',
        'width', 'height', 'alt', 'caption', 'description',
    ];

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }

    public function webpUrl(): ?string
    {
        return $this->webp_path ? asset('storage/'.$this->webp_path) : null;
    }
}
