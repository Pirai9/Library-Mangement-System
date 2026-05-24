<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Book::with('category')
            ->active()
            ->when($request->search, fn($q) => $q->search($request->search))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->available, fn($q) => $q->available())
            ->when($request->has_digital, fn($q) => $q->where('has_digital', true))
            ->when($request->language, fn($q) => $q->where('language', $request->language))
            ->when($request->sort === 'popular', fn($q) => $q->orderByDesc('borrow_count'))
            ->when($request->sort === 'rating', fn($q) => $q->orderByDesc('rating'))
            ->when($request->sort === 'newest', fn($q) => $q->orderByDesc('created_at'))
            ->when(!$request->sort, fn($q) => $q->orderBy('title'));

        $perPage = $request->per_page ?? 12;
        $books = $query->paginate($perPage);

        return response()->json(['success' => true, 'data' => $books]);
    }

    public function show(Book $book): JsonResponse
    {
        $book->load(['category', 'digitalResources', 'ratings.user']);
        return response()->json(['success' => true, 'data' => $book]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|unique:books',
            'category_id' => 'required|exists:categories,id',
            'publisher' => 'nullable|string',
            'publication_year' => 'nullable|integer|min:1000|max:' . date('Y'),
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'total_quantity' => 'required|integer|min:1',
            'shelf_location' => 'nullable|string',
            'language' => 'nullable|string',
            'pages' => 'nullable|integer',
            'price' => 'nullable|numeric',
            'tags' => 'nullable|string',
        ]);

        $validated['available_quantity'] = $validated['total_quantity'];

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('books/covers', 'public');
        }

        $book = Book::create($validated);
        $book->load('category');

        // Update category books count
        Category::where('id', $book->category_id)->increment('books_count');

        return response()->json([
            'success' => true,
            'message' => 'Book added successfully',
            'data' => $book,
        ], 201);
    }

    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'author' => 'sometimes|string|max:255',
            'isbn' => 'nullable|string|unique:books,isbn,' . $book->id,
            'category_id' => 'sometimes|exists:categories,id',
            'publisher' => 'nullable|string',
            'publication_year' => 'nullable|integer',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'total_quantity' => 'sometimes|integer|min:1',
            'shelf_location' => 'nullable|string',
            'language' => 'nullable|string',
            'pages' => 'nullable|integer',
            'price' => 'nullable|numeric',
            'tags' => 'nullable|string',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('books/covers', 'public');
        }

        // Adjust available quantity if total changed
        if (isset($validated['total_quantity'])) {
            $diff = $validated['total_quantity'] - $book->total_quantity;
            $validated['available_quantity'] = max(0, $book->available_quantity + $diff);
        }

        $book->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Book updated successfully',
            'data' => $book->fresh()->load('category'),
        ]);
    }

    public function destroy(Book $book): JsonResponse
    {
        if ($book->borrowings()->where('status', 'active')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete book with active borrowings',
            ], 422);
        }

        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }

        Category::where('id', $book->category_id)->decrement('books_count');
        $book->delete();

        return response()->json([
            'success' => true,
            'message' => 'Book deleted successfully',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->q;
        if (!$query || strlen($query) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $books = Book::with('category')
            ->active()
            ->search($query)
            ->limit(8)
            ->get(['id', 'title', 'author', 'isbn', 'cover_image', 'available_quantity']);

        return response()->json(['success' => true, 'data' => $books]);
    }

    public function popular(): JsonResponse
    {
        $books = Book::with('category')
            ->active()
            ->orderByDesc('borrow_count')
            ->limit(10)
            ->get();

        return response()->json(['success' => true, 'data' => $books]);
    }

    public function toggleFavorite(Request $request, Book $book): JsonResponse
    {
        $user = $request->user();
        $user->favorites()->toggle($book->id);
        $isFavorite = $user->favorites()->where('book_id', $book->id)->exists();

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
            'message' => $isFavorite ? 'Added to favorites' : 'Removed from favorites',
        ]);
    }

    public function getFavorites(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()->with('category')->paginate(12);
        return response()->json(['success' => true, 'data' => $favorites]);
    }
}
