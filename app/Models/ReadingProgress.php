<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'digital_resource_id', 'current_page',
        'progress_percent', 'bookmarks', 'last_read_at',
    ];

    protected $casts = [
        'bookmarks' => 'array',
        'progress_percent' => 'decimal:2',
        'last_read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function digitalResource()
    {
        return $this->belongsTo(DigitalResource::class);
    }
}
