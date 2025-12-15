<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google’s OAuth page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google.
     */
    public function callback(Request $request)
    {
        try {
            // Get the user information from Google
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            \Log::error('Google auth error: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Google authentication failed.');
        }

        try {
            // Check if the user already exists in the database
            $existingUser = User::where('email', $googleUser->getEmail())->first();

            if ($existingUser) {
                // Get student role
                $studentRole = Role::where('name', 'student')->first();

                // Mark email as verified and assign student role if missing
                $existingUser->email_verified_at = now();

                if (!$existingUser->role_id) {
                    $existingUser->role_id = $studentRole->id ?? 3;
                }

                $existingUser->save();

                // Log the user in
                Auth::login($existingUser, true);

                \Log::info('Existing user logged in: ' . $existingUser->email);
            } else {
                // Otherwise, create a new user and log them in
                // Get the student role (default role for Google sign-ups)
                $studentRole = Role::where('name', 'student')->first();

                $newUser = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(),
                    'role_id' => $studentRole->id ?? 3
                ]);

                Auth::login($newUser, true);

                \Log::info('New user created and logged in: ' . $newUser->email);
            }

            // Regenerate session to prevent fixation
            $request->session()->regenerate();

            // Redirect based on user role
            $role = Auth::user()->role->name;

            return match ($role) {
                'admin'   => redirect()->route('admin.dashboard'),
                'trainer' => redirect()->route('trainer.dashboard'),
                default   => redirect()->route('student.dashboard'),
            };

        } catch (Throwable $e) {
            \Log::error('Error in callback: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Authentication failed.');
        }
    }

    /**
     * Redirect user to appropriate dashboard based on their role.
     */
    private function redirectToDashboard(User $user)
    {
        if ($user->isRole('admin')) {
            return redirect('/admin');
        } elseif ($user->isRole('trainer')) {
            return redirect('/trainer');
        } elseif ($user->isRole('student')) {
            return redirect('/student');
        }

        // Fallback to student dashboard for Google login users
        return redirect('/student');
    }
}