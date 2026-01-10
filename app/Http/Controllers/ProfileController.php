<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Display the user's profile information.
     */
    public function showMyProfile(Request $request): Response
    {
        $user = $request->user()->load('profile', 'role');

        return Inertia::render('Profile/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
                'profile' => $user->profile ? [
                    'prefix' => $user->profile->prefix,
                    'first_name' => $user->profile->first_name,
                    'last_name' => $user->profile->last_name,
                    'phone' => $user->profile->phone,
                    'date_of_birth' => $user->profile->date_of_birth?->format('Y-m-d'),
                    'gender' => $user->profile->gender,
                    // Student
                    'sub_category' => $user->profile->sub_category,
                    'faculty' => $user->profile->faculty,
                    'major' => $user->profile->major,
                    'student_id' => $user->profile->student_id,
                    'degree_level' => $user->profile->degree_level,
                    'year_of_study' => $user->profile->year_of_study,
                    // Personnel
                    'personnel_id' => $user->profile->personnel_id,
                    'organization' => $user->profile->organization,
                    'department' => $user->profile->department,
                    'job_position' => $user->profile->job_position,
                    'employment_status' => $user->profile->employment_status,
                    'personnel_type' => $user->profile->personnel_type,
                    // External
                    'category' => $user->profile->category,
                    
                    'bio' => $user->profile->bio,
                    'has_avatar' => (bool) $user->profile->avatar_image,
                ] : null,
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function updateMyProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'organization' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        // Update user name
        $user->update(['name' => $validated['name']]);

        // Update or create profile
        $profileData = [
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'organization' => $validated['organization'] ?? null,
            'department' => $validated['department'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ];

        if ($user->profile) {
            $user->profile->update($profileData);
        } else {
            $user->profile()->create($profileData);
        }

        return Redirect::route('me.profile')->with('success', 'Profile updated successfully!');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
