<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BorrowController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DigitalResourceController;
use Illuminate\Support\Facades\Route;

// ─── Public routes ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});

// ─── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // Books (public read, admin/librarian write)
    Route::get('/books/search', [BookController::class, 'search']);
    Route::get('/books/popular', [BookController::class, 'popular']);
    Route::get('/books/favorites', [BookController::class, 'getFavorites']);
    Route::post('/books/{book}/favorite', [BookController::class, 'toggleFavorite']);
    Route::apiResource('books', BookController::class)->except(['store', 'update', 'destroy']);

    // Admin/Librarian book management
    Route::middleware('role:admin,librarian')->group(function () {
        Route::post('/books', [BookController::class, 'store']);
        Route::post('/books/{book}', [BookController::class, 'update']); // POST for file uploads
        Route::delete('/books/{book}', [BookController::class, 'destroy']);
    });

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::middleware('role:admin,librarian')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    });

    // Borrowings
    Route::get('/borrowings/my', [BorrowController::class, 'myBorrowings']);
    Route::post('/borrowings/borrow', [BorrowController::class, 'borrow']);
    Route::middleware('role:admin,librarian')->group(function () {
        Route::get('/borrowings', [BorrowController::class, 'index']);
        Route::put('/borrowings/{borrowing}/return', [BorrowController::class, 'returnBook']);
        Route::post('/borrowings/update-overdue', [BorrowController::class, 'updateOverdue']);
    });

    // Reservations
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'reserve']);
    Route::put('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    // Analytics
    Route::prefix('analytics')->middleware('role:admin,librarian')->group(function () {
        Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('/monthly-borrowings', [AnalyticsController::class, 'monthlyBorrowings']);
        Route::get('/category-distribution', [AnalyticsController::class, 'categoryDistribution']);
        Route::get('/top-borrowers', [AnalyticsController::class, 'topBorrowers']);
        Route::get('/fine-stats', [AnalyticsController::class, 'fineStats']);
        Route::get('/summary', [AnalyticsController::class, 'summary']);
    });

    // User management (admin only, except listing students which librarians can do)
    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin,librarian');
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class)->except(['index']);
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    });

    // Digital Resources
    Route::get('/digital-resources', [DigitalResourceController::class, 'index']);
    Route::get('/digital-resources/{digitalResource}', [DigitalResourceController::class, 'show']);
    Route::get('/digital-resources/{digitalResource}/progress', [DigitalResourceController::class, 'getProgress']);
    Route::put('/digital-resources/{digitalResource}/progress', [DigitalResourceController::class, 'updateProgress']);
    Route::middleware('role:admin,librarian')->group(function () {
        Route::post('/digital-resources', [DigitalResourceController::class, 'store']);
        Route::delete('/digital-resources/{digitalResource}', [DigitalResourceController::class, 'destroy']);
    });
});
