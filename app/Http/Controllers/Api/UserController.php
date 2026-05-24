<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, fn($q) => $q->where(function($qu) use ($request) {
                $qu->where('name', 'LIKE', "%{$request->search}%")
                   ->orWhere('email', 'LIKE', "%{$request->search}%")
                   ->orWhere('student_id', 'LIKE', "%{$request->search}%");
            }))
            ->when($request->active !== null, fn($q) => $q->where('is_active', $request->active))
            ->withCount(['borrowings as total_borrows', 'borrowings as active_borrows' => function($q) {
                $q->where('status', 'active');
            }])
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount([
            'borrowings as total_borrows',
            'borrowings as active_borrows' => fn($q) => $q->where('status', 'active'),
            'reservations as pending_reservations' => fn($q) => $q->where('status', 'pending'),
        ]);

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,librarian,student',
            'phone' => 'nullable|string',
            'student_id' => 'nullable|string|unique:users',
            'department' => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:admin,librarian,student',
            'phone' => 'nullable|string',
            'department' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($request->password) {
            $request->validate(['password' => 'min:8']);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->borrowings()->where('status', 'active')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete user with active borrowings',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    public function toggleStatus(User $user): JsonResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated',
            'is_active' => $user->is_active,
        ]);
    }
}
