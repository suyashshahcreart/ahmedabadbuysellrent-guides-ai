<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'ai_monthly_credit',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function author()
    {
        return $this->hasOne(Author::class);
    }

    public function creditLedgers()
    {
        return $this->hasMany(AiCreditLedger::class);
    }

    public function aiUsageLogs()
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function requiresAiCreditCheck(): bool
    {
        return ! $this->hasAnyRole(['superAdmin', 'admin']);
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        $name = urlencode($this->name ?: 'Admin');

        return 'https://ui-avatars.com/api/?name='.$name.'&background=000040&color=c9a42e&size=64';
    }
}
