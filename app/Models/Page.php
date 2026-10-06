<?php

namespace App\Models;

use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes, HasSeo, HasFaqs, HasRevisions, Publishable;

    protected $fillable = [
        'title', 'slug', 'path', 'excerpt', 'content', 'featured_media_id',
        'status', 'published_at', 'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function featuredMedia()
    {
        return $this->belongsTo(MediaAsset::class, 'featured_media_id');
    }

    public function publicUrl(): string
    {
        $path = '/'.ltrim($this->path, '/');
        if (! str_ends_with($path, '/')) {
            $path .= '/';
        }

        return rtrim(config('app.url'), '/').$path;
    }
}
