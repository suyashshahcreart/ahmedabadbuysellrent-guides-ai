<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'seo_title', 'meta_description', 'canonical_url', 'robots',
        'seo_keywords', 'og_title', 'og_description', 'og_image',
        'og_image_media_id', 'twitter_title', 'twitter_description', 'twitter_image',
    ];

    public function seoable()
    {
        return $this->morphTo();
    }

    public function ogImageMedia()
    {
        return $this->belongsTo(MediaAsset::class, 'og_image_media_id');
    }
}
