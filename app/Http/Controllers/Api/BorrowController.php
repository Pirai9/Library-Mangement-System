<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\Book;
use App\Models\Notification;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BorrowController extends Controller
{
    // Admin/Librarian: get all borrowings
    public function index(Request $request): JsonResponse
    {
        $query = Borrowing::with(['user', 'book.category'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->book_id, fn($q) => $q->where('book_id', $request->book_id))
            ->when($request->search, fn($q) => $q->whereHas('book', function($bq) use ($request) {
                $bq->where('title', 'LIKE', "%{$request->search}%");
            })->orWhereHas('user', function($uq) use ($request) {
                $uq->where('name', 'LIKE', "%{$request->search}%");
            }))
            ->orderByDesc('created_at');

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->per_page ?? 15),
        ]);
    }

    // Student: get own borrowings
    public function myBorrowings(Request $request): JsonResponse
    {
        $borrowings = Borrowing::with(['book.category'])
            ->where('user_id', $request->user()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('borrowed_at')
            ->paginate(10);

        return response()->json(['success' => true, 'data' => $borrowings]);
    }

    public function borrow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'user_id' => 'sometimes|exists:users,id',
            'due_days' => 'sometimes|integer|min:1|max:30',
            'notes' => 'nullable|string',
        ]);

        $userId = $validated['user_id'] ?? $request->user()->id;
        $book = Book::findOrFail($validated['book_id']);

        // Check availability
        if ($book->available_quantity < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Book is not available. You can reserve it instead.',
            ], 422);
        }

        // Check if user already has this book
        $existing = Borrowing::where('user_id', $userId)
            ->where('book_id', $book->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have this book borrowed.',
            ], 422);
        }

        $dueDays = $validated['due_days'] ?? 14;
        $borrowedAt = Carbon::now();
        $dueDate = $borrowedAt->copy()->addDays($dueDays);

        $borrowing = Borrowing::create([
            'user_id' => $userId,
            'book_id' => $book->id,
            'borrowed_at' => $borrowedAt,
            'due_date' => $dueDate,
            'status' => 'active',
            'issued_by' => $request->user()->name,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Update book availability
        $book->decrement('available_quantity');
        $book->increment('borrow_count');

        // Send notification
        Notification::create([
            'user_id' => $userId,
            'title' => 'Book Borrowed Successfully 📚',
            'message' => "You have borrowed \"{$book->title}\". Due date: {$dueDate->format('M d, Y')}. Keep it safe!",
            'type' => 'borrow',
            'action_url' => '/borrowed-books',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Book borrowed successfully',
            'data' => $borrowing->load(['book.category', 'user']),
        ], 201);
    }

    public function returnBook(Request $request, Borrowing $borrowing): JsonResponse
    {
        if ($borrowing->status === 'returned') {
            return response()->json([
                'success' => false,
                'message' => 'Book already returned',
            ], 422);
        }

        $returnedAt = Carbon::now();
        $fine = $borrowing->calculated_fine;

        $borrowing->update([
            'status' => 'returned',
            'returned_at' => $returnedAt,
            'fine_amount' => $fine,
        ]);

        // Update book availability
        $borrowing->book->increment('available_quantity');

        // Check for pending reservations
        $reservation = Reservation::where('book_id', $borrowing->book_id)
            ->where('status', 'pending')
            ->orderBy('queue_position')
            ->first();

        if ($reservation) {
            Notification::create([
                'user_id' => $reservation->user_id,
                'title' => 'Book Now Available! 🎉',
                'message' => "Great news! \"{$borrowing->book->title}\" is now available. Please pick it up within 48 hours.",
                'type' => 'reservation',
                'action_url' => '/reservations',
            ]);
        }

        // Fine notification
        if ($fine > 0) {
            Notification::create([
                'user_id' => $borrowing->user_id,
                'title' => 'Return Fine Applied 💰',
                'message' => "A fine of \${$fine} has been applied for returning \"{$borrowing->book->title}\" late. Please pay at the library desk.",
                'type' => 'fine',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Book returned successfully',
            'fine_amount' => $fine,
            'data' => $borrowing->fresh()->load(['book', 'user']),
        ]);
    }

    public function updateOverdue(): JsonResponse
    {
        $updated = Borrowing::where('status', 'active')
            ->where('due_date', '<', Carbon::now())
            ->update(['status' => 'overdue']);

        return response()->json([
            'success' => true,
            'message' => "{$updated} borrowings marked as overdue",
        ]);
    }
}
