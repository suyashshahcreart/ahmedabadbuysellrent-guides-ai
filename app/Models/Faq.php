<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['faqable_type', 'faqable_id', 'question', 'answer', 'sort_order', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function faqable()
    {
        return $this->morphTo();
    }
}
