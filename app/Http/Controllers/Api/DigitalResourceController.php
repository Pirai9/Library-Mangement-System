<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalResource;
use App\Models\ReadingProgress;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DigitalResourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $resources = DigitalResource::with(['book.category', 'readingProgress' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }])
            ->where('is_active', true)
            ->when($request->type, fn($q) => $q->where('file_type', $request->type))
            ->when($request->search, fn($q) => $q->where('title', 'LIKE', "%{$request->search}%")
                ->orWhereHas('book', fn($bq) => $bq->where('title', 'LIKE', "%{$request->search}%")))
            ->orderByDesc('created_at')
            ->paginate(12);

        $resources->getCollection()->transform(function ($resource) use ($user) {
            $progress = $resource->readingProgress->first();
            $resource->reading_progress = $progress;
            return $resource;
        });

        return response()->json(['success' => true, 'data' => $resources]);
    }

    public function show(DigitalResource $digitalResource): JsonResponse
    {
        $digitalResource->load('book.category');
        return response()->json(['success' => true, 'data' => $digitalResource]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->has('title') && $request->book_id) {
            $book = Book::find($request->book_id);
            if ($book) {
                $request->merge(['title' => $book->title]);
            }
        }

        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'title' => 'required|string|max:255',
            'file_type' => 'required|in:pdf,epub,audio,video',
            'file' => 'required|file|max:102400', // 100MB
            'thumbnail' => 'nullable|image|max:2048',
        ]);

        $file = $request->file('file');
        $validated['file_path'] = $file->store('digital/' . $validated['file_type'], 'public');
        $validated['file_size'] = $file->getSize();

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('digital/thumbnails', 'public');
        }

        unset($validated['file']);
        $resource = DigitalResource::create($validated);

        Book::where('id', $validated['book_id'])->update(['has_digital' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Digital resource uploaded successfully',
            'data' => $resource->load('book'),
        ], 201);
    }

    public function updateProgress(Request $request, DigitalResource $digitalResource): JsonResponse
    {
        $validated = $request->validate([
            'current_page' => 'sometimes|integer|min:1',
            'progress_percent' => 'sometimes|numeric|between:0,100',
            'bookmarks' => 'nullable|array',
        ]);

        $progress = ReadingProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'digital_resource_id' => $digitalResource->id],
            array_merge($validated, ['last_read_at' => now()])
        );

        return response()->json(['success' => true, 'data' => $progress]);
    }

    public function getProgress(Request $request, DigitalResource $digitalResource): JsonResponse
    {
        $progress = ReadingProgress::firstOrCreate(
            ['user_id' => $request->user()->id, 'digital_resource_id' => $digitalResource->id],
            ['current_page' => 1, 'progress_percent' => 0]
        );

        return response()->json(['success' => true, 'data' => $progress]);
    }

    public function destroy(DigitalResource $digitalResource): JsonResponse
    {
        Storage::disk('public')->delete($digitalResource->file_path);
        $digitalResource->delete();

        return response()->json(['success' => true, 'message' => 'Resource deleted']);
    }
}
