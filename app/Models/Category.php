<?php

namespace App\Models;

use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasSeo, HasFaqs;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'nav_label', 'tag_label', 'icon',
        'description', 'image_path', 'sort_order', 'status',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function displayLabel(): string
    {
        return $this->tag_label ?: $this->name;
    }

    public function publicUrl(): string
    {
        return rtrim(config('app.url'), '/').'/'.$this->slug.'/';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isIndexable(): bool
    {
        $robots = optional($this->seo)->robots ?? 'index, follow';

        return $this->isPublished() && ! str_contains(strtolower((string) $robots), 'noindex');
    }
}
