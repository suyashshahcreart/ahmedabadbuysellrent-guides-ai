<?php

namespace App\Models;

use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;

class Locality extends Model
{
    use HasSeo, HasFaqs, HasRevisions, Publishable;

    protected $fillable = [
        'name', 'slug', 'short', 'tags', 'summary', 'overview', 'property_market',
        'average_price', 'rental_information', 'connectivity', 'schools', 'hospitals',
        'shopping', 'lifestyle', 'investment_potential', 'pros', 'cons',
        'featured_media_id', 'sort_order', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'pros' => 'array',
            'cons' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function featuredMedia()
    {
        return $this->belongsTo(MediaAsset::class, 'featured_media_id');
    }

    public function publicUrl(): string
    {
        return rtrim(config('app.url'), '/').'/areas/'.$this->slug.'/';
    }
}
