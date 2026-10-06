<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Publishable
{
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && ($this->published_at === null || $this->published_at->lte(now()));
    }

    public function isIndexable(): bool
    {
        $robots = optional($this->seo)->robots ?? 'index, follow';

        return $this->isPublished() && ! str_contains(strtolower((string) $robots), 'noindex');
    }
}
