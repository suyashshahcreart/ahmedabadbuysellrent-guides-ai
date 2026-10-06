<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;

trait HasSeo
{
    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function seoOrNew(): SeoMeta
    {
        return $this->seo ?: $this->seo()->make();
    }

    public function seoSchemas()
    {
        return $this->morphMany(\App\Models\SeoSchema::class, 'schemaable');
    }
}
