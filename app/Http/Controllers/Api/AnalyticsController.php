<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $totalBooks = Book::active()->count();
        $availableBooks = Book::active()->available()->count();
        $borrowedBooks = Borrowing::where('status', 'active')->count();
        $overdueBooks = Borrowing::where('status', 'overdue')
            ->orWhere(function($q) {
                $q->where('status', 'active')->where('due_date', '<', Carbon::now());
            })->count();
        $totalStudents = User::where('role', 'student')->active()->count();
        $totalReservations = Reservation::where('status', 'pending')->count();
        $totalFines = Borrowing::where('fine_paid', false)->where('fine_amount', '>', 0)->sum('fine_amount');

        $popularBooks = Book::with('category')
            ->active()
            ->orderByDesc('borrow_count')
            ->limit(5)
            ->get(['id', 'title', 'author', 'cover_image', 'borrow_count', 'rating']);

        $recentActivity = Borrowing::with(['user', 'book'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $categoryStats = Category::withCount(['books as book_count'])
            ->having('book_count', '>', 0)
            ->orderByDesc('book_count')
            ->limit(6)
            ->get(['id', 'name', 'color', 'icon']);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_books' => $totalBooks,
                    'available_books' => $availableBooks,
                    'borrowed_books' => $borrowedBooks,
                    'overdue_books' => $overdueBooks,
                    'total_students' => $totalStudents,
                    'total_reservations' => $totalReservations,
                    'total_fines' => round($totalFines, 2),
                ],
                'popular_books' => $popularBooks,
                'recent_activity' => $recentActivity,
                'category_stats' => $categoryStats,
            ],
        ]);
    }

    public function monthlyBorrowings(Request $request): JsonResponse
    {
        $year = $request->year ?? Carbon::now()->year;
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $borrowed = Borrowing::whereYear('borrowed_at', $year)->whereMonth('borrowed_at', $month)->count();
            $returned = Borrowing::whereYear('returned_at', $year)->whereMonth('returned_at', $month)->where('status', 'returned')->count();
            $data[] = [
                'month' => Carbon::createFromDate($year, $month, 1)->format('M'),
                'borrowed' => $borrowed,
                'returned' => $returned,
            ];
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function categoryDistribution(): JsonResponse
    {
        $data = Category::withCount('books')
            ->having('books_count', '>', 0)
            ->get(['id', 'name', 'color', 'books_count']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function topBorrowers(): JsonResponse
    {
        $data = User::withCount(['borrowings as total_borrows'])
            ->where('role', 'student')
            ->orderByDesc('total_borrows')
            ->limit(10)
            ->get(['id', 'name', 'email', 'student_id', 'department', 'avatar', 'total_borrows']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function fineStats(): JsonResponse
    {
        $total = Borrowing::sum('fine_amount');
        $paid = Borrowing::where('fine_paid', true)->sum('fine_amount');
        $unpaid = Borrowing::where('fine_paid', false)->where('fine_amount', '>', 0)->sum('fine_amount');

        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthly[] = [
                'month' => $date->format('M'),
                'amount' => (float) Borrowing::whereYear('updated_at', $date->year)
                    ->whereMonth('updated_at', $date->month)
                    ->where('fine_amount', '>', 0)
                    ->sum('fine_amount'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => compact('total', 'paid', 'unpaid', 'monthly'),
        ]);
    }

    public function summary(): JsonResponse
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $thisMonthBorrows = Borrowing::where('borrowed_at', '>=', $thisMonth)->count();
        $lastMonthBorrows = Borrowing::whereBetween('borrowed_at', [$lastMonth, $thisMonth])->count();

        $newUsers = User::where('created_at', '>=', $thisMonth)->count();
        $newBooks = Book::where('created_at', '>=', $thisMonth)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'this_month_borrows' => $thisMonthBorrows,
                'last_month_borrows' => $lastMonthBorrows,
                'borrow_growth' => $lastMonthBorrows > 0
                    ? round((($thisMonthBorrows - $lastMonthBorrows) / $lastMonthBorrows) * 100, 1)
                    : 100,
                'new_users_this_month' => $newUsers,
                'new_books_this_month' => $newBooks,
            ],
        ]);
    }
}
