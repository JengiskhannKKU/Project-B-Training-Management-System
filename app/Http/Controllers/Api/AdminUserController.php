<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * List users with optional filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['nullable', 'string', Rule::in(['admin', 'trainer', 'student'])],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::with('role')->latest();

        if (isset($validated['role'])) {
            $query->whereHas('role', function ($q) use ($validated) {
                $q->where('name', $validated['role']);
            });
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Admin-only endpoint to create a user with a specified role.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(['admin', 'trainer', 'student'])],
        ]);

        $role = Role::where('name', $data['role'])->firstOrFail();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $role->id,
            'status' => 'active',
            // Skip email verification flow for now.
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }

    /**
     * Update a user's profile, role, or status.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['sometimes', 'required', 'string', Rule::in(['admin', 'trainer', 'student'])],
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        if (isset($data['role'])) {
            $role = Role::where('name', $data['role'])->firstOrFail();
            $data['role_id'] = $role->id;
            unset($data['role']);
        }

        $user->update($data);
        $user->load('role');

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user,
        ]);
    }

    /**
     * Deactivate a user instead of hard deleting.
     */
    public function destroy(User $user): JsonResponse
    {
        return $this->deactivate($user);
    }

    /**
     * Mark a user as inactive.
     */
    public function deactivate(User $user): JsonResponse
    {
        if ($user->status !== 'inactive') {
            $user->update(['status' => 'inactive']);
        }

        return response()->json([
            'message' => 'User deactivated successfully.',
            'data' => $user,
        ]);
    }
}
