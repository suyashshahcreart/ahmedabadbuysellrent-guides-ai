<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAngle extends Model
{
    protected $fillable = [
        'key', 'name', 'audience', 'intent', 'category_slug',
        'title_pattern', 'brief', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
