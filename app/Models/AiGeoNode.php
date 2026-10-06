<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeoNode extends Model
{
    protected $fillable = ['type', 'name', 'slug', 'parent_id', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
