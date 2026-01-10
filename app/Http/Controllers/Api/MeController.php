<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class MeController extends Controller
{
    /**
     * Return current user info with profile and avatar flag.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('profile', 'role');

        $avatarPresent = (bool) optional($user->profile)->avatar_image;

        return response()->json([
            'user' => $user,
            'profile' => $user->profile,
            'avatar_present' => $avatarPresent,
        ]);
    }

    /**
     * Update current user's profile text info.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'prefix' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            // Student
            'sub_category' => ['nullable', 'string', 'max:255'],
            'faculty' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'student_id' => ['nullable', 'string', 'max:255'],
            'degree_level' => ['nullable', 'string', 'max:255'],
            'year_of_study' => ['nullable', 'string', 'max:255'],
            // Personnel
            'personnel_id' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'job_position' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['nullable', 'string', 'max:255'],
            'personnel_type' => ['nullable', 'string', 'max:255'],
            // External
            'category' => ['nullable', 'string', 'max:255'],
            
            'bio' => ['nullable', 'string'],
        ]);

        if (isset($data['name'])) {
            $user->update(['name' => $data['name']]);
            unset($data['name']);
        }

        $profile = $user->profile ?: $user->profile()->create();
        $profile->update($data);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh('profile', 'role'),
        ]);
    }

    /**
     * Upload and store current user's avatar as BLOB.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'], // Max 2MB
        ]);

        $file = $validated['avatar'];
        $content = file_get_contents($file->getRealPath());
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        $profile = $user->profile ?: $user->profile()->create();
        $profile->update([
            'avatar_image' => $content,
            'avatar_mime_type' => $mime,
        ]);

        return response()->json([
            'message' => 'Avatar updated successfully.',
        ]);
    }

    /**
     * Stream the current user's avatar.
     */
    public function showAvatar(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user()->load('profile');
        $profile = $user->profile;

        if ($profile && $profile->avatar_image) {
            $mime = $profile->avatar_mime_type ?? 'application/octet-stream';

            return response($profile->avatar_image, 200, [
                'Content-Type' => $mime,
                'Content-Length' => strlen($profile->avatar_image),
            ]);
        }

        $fallbackPath = public_path('default-avatar.svg');
        if (is_file($fallbackPath)) {
            $fallback = file_get_contents($fallbackPath);

            return response($fallback, 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Length' => strlen($fallback),
            ]);
        }

        abort(404, 'Avatar not found');
    }

    /**
     * Delete the current user's avatar.
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('profile');
        $profile = $user->profile;

        if (!$profile || !$profile->avatar_image) {
            return response()->json([
                'message' => 'No avatar to delete.',
            ], 404);
        }

        $profile->update([
            'avatar_image' => null,
            'avatar_mime_type' => null,
        ]);

        return response()->json([
            'message' => 'Avatar deleted successfully.',
        ]);
    }

    /**
     * Stream another user's avatar (admin only, enforced via route middleware).
     */
    public function showUserAvatar(User $user): Response
    {
        $profile = $user->profile;

        if ($profile && $profile->avatar_image) {
            $mime = $profile->avatar_mime_type ?? 'application/octet-stream';

            return response($profile->avatar_image, 200, [
                'Content-Type' => $mime,
                'Content-Length' => strlen($profile->avatar_image),
            ]);
        }

        $fallbackPath = public_path('default-avatar.svg');
        if (is_file($fallbackPath)) {
            $fallback = file_get_contents($fallbackPath);

            return response($fallback, 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Length' => strlen($fallback),
            ]);
        }

        abort(404, 'Avatar not found');
    }
}
