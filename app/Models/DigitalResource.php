<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigitalResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id', 'title', 'file_path', 'file_type',
        'file_size', 'thumbnail', 'duration', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    protected $appends = ['file_url'];
}
