<?php

namespace App\Models;

use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes, HasSeo, HasFaqs, HasRevisions, Publishable;

    protected $fillable = [
        'type', 'title', 'slug', 'path', 'excerpt', 'content', 'featured_media_id',
        'author_id', 'category_id', 'status', 'published_at', 'scheduled_at',
        'reading_time', 'featured', 'trending', 'canonical_url', 'created_by',
        'source', 'ai_run_id', 'ai_seed_key', 'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'featured' => 'boolean',
            'trending' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function featuredMedia()
    {
        return $this->belongsTo(MediaAsset::class, 'featured_media_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
