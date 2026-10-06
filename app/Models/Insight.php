<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;

class Insight extends Model
{
    use HasSeo, Publishable;

    protected $fillable = [
        'title', 'slug', 'stat', 'label', 'body', 'content', 'status', 'published_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function publicUrl(): string
    {
        return url('/market/'.$this->slug.'/');
    }
}
