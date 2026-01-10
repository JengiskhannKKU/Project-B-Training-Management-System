<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'prefix' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birthdate' => 'nullable|date',
            'gender' => 'nullable|string|max:50',
            // Internal - Student
            'sub_category' => 'nullable|string|max:255',
            'faculty' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'student_id' => 'nullable|string|max:255',
            'degree_level' => 'nullable|string|max:255',
            'year_of_study' => 'nullable|string|max:255',
            // Internal - Personnel
            'personnel_id' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'job_position' => 'nullable|string|max:255',
            'employment_status' => 'nullable|string|max:255',
            'personnel_type' => 'nullable|string|max:255',
            // External
            'category' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
        ]);

        $traineeRole = Role::firstOrCreate(
            ['name' => 'trainee'],
            ['label' => 'Trainee']
        );

        $name = $request->name; // Already constructed in frontend
        if (!$name) {
            $name = trim($request->first_name . ' ' . $request->last_name);
        }

        $user = User::create([
            'name' => $name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $traineeRole->id,
            'status' => 'active',
            // Temporarily bypass email verification until mail is configured.
            'email_verified_at' => now(),
        ]);

        // Map frontend fields to database columns
        $profileData = [
            'prefix' => $request->prefix,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'date_of_birth' => $request->birthdate,
            'gender' => $request->gender,
            'sub_category' => $request->sub_category,
            'faculty' => $request->faculty,
            'major' => $request->major,
            'student_id' => $request->student_id,
            'degree_level' => $request->degree_level,
            'year_of_study' => $request->year_of_study,
            'personnel_id' => $request->personnel_id,
            'organization' => $request->organization_name ?? $request->organization, // Handle both external and internal organization fields
            'department' => $request->department,
            'job_position' => $request->job_position,
            'employment_status' => $request->employment_status,
            'personnel_type' => $request->personnel_type,
            'category' => $request->category,
        ];

        $user->profile()->create($profileData);

        // Skip sending verification email until email configuration is ready.
        // event(new Registered($user));

        Auth::login($user);

        // Redirect based on user role
        $role = Auth::user()->role->name;

        return match ($role) {
            'admin'   => redirect()->route('admin.dashboard'),
            'trainer' => redirect()->route('trainer.dashboard'),
            default   => redirect()->route('trainee.programs.index'),
        };
    }
}
