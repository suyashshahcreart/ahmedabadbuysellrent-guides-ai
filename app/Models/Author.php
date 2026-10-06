<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasSeo;

    protected $fillable = ['user_id', 'name', 'slug', 'bio', 'image_path', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function publicUrl(): string
    {
        return url('/authors/'.$this->slug.'/');
    }
}
