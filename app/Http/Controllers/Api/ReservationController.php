<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Book;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Reservation::with(['user', 'book.category']);

        if ($user->role === 'student') {
            $query->where('user_id', $user->id);
        }

        $reservations = $query
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('queue_position')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $reservations]);
    }

    public function reserve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $book = Book::findOrFail($validated['book_id']);

        // Check if already reserved
        $existing = Reservation::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a reservation for this book.',
            ], 422);
        }

        // Get queue position
        $queuePosition = Reservation::where('book_id', $book->id)
            ->where('status', 'pending')
            ->count() + 1;

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'reserved_at' => Carbon::now(),
            'expiry_date' => Carbon::now()->addDays(7),
            'status' => 'pending',
            'queue_position' => $queuePosition,
            'notes' => $validated['notes'] ?? null,
        ]);

        $book->increment('reservation_count');

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Reservation Confirmed 🔖',
            'message' => "You are #{$queuePosition} in queue for \"{$book->title}\". We'll notify you when it's available.",
            'type' => 'reservation',
            'action_url' => '/reservations',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Reserved successfully! You are #{$queuePosition} in queue.",
            'data' => $reservation->load(['book.category', 'user']),
        ], 201);
    }

    public function cancel(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'student' && $reservation->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $reservation->update(['status' => 'cancelled']);

        // Reorder queue
        Reservation::where('book_id', $reservation->book_id)
            ->where('status', 'pending')
            ->where('queue_position', '>', $reservation->queue_position)
            ->decrement('queue_position');

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully',
        ]);
    }
}
