<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['location', 'name'];

    public function items()
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->where('status', true)->orderBy('sort_order');
    }

    public function allItems()
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }
}
