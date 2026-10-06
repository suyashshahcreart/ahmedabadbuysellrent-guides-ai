<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoTemplate extends Model
{
    protected $fillable = [
        'page_type', 'model_type', 'title_template', 'description_template', 'h1_template',
        'schema_type', 'schema_template_json', 'default_robots', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
