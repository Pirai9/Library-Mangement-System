<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'color', 'icon', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function getActiveBooksCountAttribute(): int
    {
        return $this->books()->where('is_active', true)->count();
    }

    protected $appends = ['active_books_count'];
}
