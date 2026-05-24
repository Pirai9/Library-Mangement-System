<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'author', 'isbn', 'category_id', 'publisher',
        'publication_year', 'description', 'cover_image', 'total_quantity',
        'available_quantity', 'shelf_location', 'language', 'pages',
        'price', 'tags', 'qr_code', 'borrow_count', 'rating',
        'has_digital', 'is_active',
    ];

    protected $casts = [
        'has_digital' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function digitalResources()
    {
        return $this->hasMany(DigitalResource::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'book_favorites');
    }

    public function ratings()
    {
        return $this->hasMany(BookRating::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('available_quantity', '>', 0);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('author', 'LIKE', "%{$search}%")
              ->orWhere('isbn', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    // Computed
    public function getIsAvailableAttribute(): bool
    {
        return $this->available_quantity > 0;
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }
        return null;
    }

    protected $appends = ['is_available', 'cover_image_url'];
}
