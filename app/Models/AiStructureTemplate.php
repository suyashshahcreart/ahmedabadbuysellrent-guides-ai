<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiStructureTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'content_type', 'sections', 'keyword_rules', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'keyword_rules' => 'array',
            'status' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
