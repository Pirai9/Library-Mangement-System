<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Borrowing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'book_id', 'borrowed_at', 'due_date',
        'returned_at', 'status', 'fine_amount', 'fine_paid',
        'notes', 'issued_by',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_date' => 'datetime',
        'returned_at' => 'datetime',
        'fine_paid' => 'boolean',
        'fine_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function getDaysOverdueAttribute(): int
    {
        if ($this->status === 'returned') return 0;
        $now = Carbon::now();
        $due = Carbon::parse($this->due_date);
        return $now->gt($due) ? $now->diffInDays($due) : 0;
    }

    public function getCalculatedFineAttribute(): float
    {
        $daysOverdue = $this->days_overdue;
        return $daysOverdue > 0 ? $daysOverdue * 5.00 : 0; // $5 per day
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
                     ->orWhere(function($q) {
                         $q->where('status', 'active')
                           ->where('due_date', '<', Carbon::now());
                     });
    }

    protected $appends = ['days_overdue', 'calculated_fine'];
}
