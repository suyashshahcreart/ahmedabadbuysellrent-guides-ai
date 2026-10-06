<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = ['menu_id', 'parent_id', 'label', 'url', 'target', 'sort_order', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->where('status', true)->orderBy('sort_order');
    }
}
