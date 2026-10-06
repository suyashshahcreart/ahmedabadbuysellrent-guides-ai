<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelatedItem extends Model
{
    protected $fillable = ['source_type', 'source_id', 'target_type', 'target_id', 'sort_order'];

    public function source()
    {
        return $this->morphTo();
    }

    public function target()
    {
        return $this->morphTo();
    }
}
