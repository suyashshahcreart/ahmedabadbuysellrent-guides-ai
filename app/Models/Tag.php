<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasSeo;

    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'status'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isPublished(): bool
    {
        return ($this->status ?? 'published') === 'published';
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }

    public function publicUrl(): string
    {
        return rtrim(config('app.url'), '/').'/tags/'.$this->slug.'/';
    }

    public function isIndexable(): bool
    {
        $robots = optional($this->seo)->robots ?? 'index, follow';

        return $this->isPublished() && ! str_contains(strtolower((string) $robots), 'noindex');
    }
}
