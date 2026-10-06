<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCreditLedger extends Model
{
    protected $fillable = [
        'user_id', 'actor_id', 'old_credit', 'new_credit', 'forced', 'note',
    ];

    protected function casts(): array
    {
        return [
            'forced' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
