<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$redirectToRoleDashboard = function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $role = Auth::user()->role->name ?? 'trainee';

    return redirect()->route(match ($role) {
        'admin' => 'admin.dashboard',
        'trainer' => 'trainer.courses.index',
        default => 'courses.index',
    });
};

Route::get('/', $redirectToRoleDashboard);

Route::get('/dashboard', $redirectToRoleDashboard)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('language/{locale}', function ($locale) {
    if (in_array($locale, ['th', 'en'])) {
        session()->put('locale', $locale);
        // Set the app locale immediately so HandleInertiaRequests middleware picks it up
        App::setLocale($locale);
    }
    return redirect()->back();
})->name('language.switch');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/me/profile', [ProfileController::class, 'showMyProfile'])->name('me.profile');
    Route::put('/me/profile', [ProfileController::class, 'updateMyProfile'])->name('me.profile.update');
    Route::get('/me/enrollments', function () {
        return Inertia::render('Trainee/Enrollments/Index');
    })->name('me.enrollments');
    Route::get('/me/enrollments/{id}', function ($id) {
        return Inertia::render('Trainee/Enrollments/Show', [
            'enrollmentId' => $id,
        ]);
    })->name('me.enrollments.show');
    Route::get('/me/certificates', function () {
        $user = Auth::user();
        $certificates = \App\Models\Certificate::with(['course', 'session.course'])
            ->where('user_id', $user->id)
            ->orderBy('issued_at', 'desc')
            ->get()
            ->map(function ($certificate) use ($user) {
                // Check if evaluation exists for this certificate
                $hasEvaluation = true;
                $attendanceRate = 100;
                $blockReason = null;

                if ($certificate->session_id) {
                    // Check evaluation
                    $hasEvaluation = \App\Models\Evaluation::where('session_id', $certificate->session_id)
                        ->where('user_id', $certificate->user_id)
                        ->exists();

                    // Check attendance rate (from multi-day attendance system)
                    $enrollment = \App\Models\Enrollment::where('session_id', $certificate->session_id)
                        ->where('user_id', $user->id)
                        ->first();

                    if ($enrollment) {
                        // Use attendance_percent calculated by multi-day attendance system
                        $attendanceRate = $enrollment->attendance_percent ?? 0;
                    }

                    // Determine block reason
                    if ($attendanceRate < 80) {
                        $blockReason = 'insufficient_attendance';
                    } elseif (!$hasEvaluation) {
                        $blockReason = 'missing_feedback';
                    }
                }

                return array_merge($certificate->toArray(), [
                    'has_evaluation' => $hasEvaluation,
                    'attendance_rate' => $attendanceRate,
                    'can_download' => $hasEvaluation && $attendanceRate >= 80,
                    'block_reason' => $blockReason,
                ]);
            });

        return Inertia::render('Trainee/Certificates/Index', [
            'certificates' => $certificates
        ]);
    })->name('me.certificates');

    Route::get('/certificates/{id}', function ($id) {
        $certificate = \App\Models\Certificate::with([
            'user',
            'course',
            'session.course',
            'issuer'
        ])->findOrFail($id);

        $user = Auth::user();
        $canView = $user->id === $certificate->user_id ||
            $user->role->name === 'admin' ||
            ($user->role->name === 'trainer' && (
                $certificate->session?->trainer_id === $user->id ||
                $certificate->course?->owner_id === $user->id
            ));

        if (!$canView) {
            abort(403, 'Unauthorized to view this certificate');
        }

        // Add evaluation and attendance data
        $hasEvaluation = true;
        $attendanceRate = 100;
        $blockReason = null;

        if ($certificate->session_id) {
            $hasEvaluation = \App\Models\Evaluation::where('session_id', $certificate->session_id)
                ->where('user_id', $certificate->user_id)
                ->exists();

            $enrollment = \App\Models\Enrollment::where('session_id', $certificate->session_id)
                ->where('user_id', $certificate->user_id)
                ->first();

            if ($enrollment) {
                $attendanceRate = $enrollment->attendance_percent ?? 0;
            }

            if ($attendanceRate < 80) {
                $blockReason = 'insufficient_attendance';
            } elseif (!$hasEvaluation) {
                $blockReason = 'missing_feedback';
            }
        }

        $certificateData = array_merge($certificate->toArray(), [
            'has_evaluation' => $hasEvaluation,
            'attendance_rate' => $attendanceRate,
            'can_download' => $hasEvaluation && $attendanceRate >= 80,
            'block_reason' => $blockReason,
        ]);

        return Inertia::render('Certificates/Show', [
            'certificateId' => $id,
            'certificate' => $certificateData,
        ]);
    })->name('certificates.show');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return Inertia::render('Admin/AdminDashboard');
    })->name('admin.dashboard');

    Route::get('/admin/users', function () {
        return Inertia::render('Admin/Users');
    })->name('admin.users');

    Route::get('/admin/users/{id}/edit', function ($id) {
        return Inertia::render('Admin/Users', ['editUserId' => $id]);
    })->name('admin.users.edit');

    Route::get('/admin/categories', function () {
        $categories = \App\Models\Category::withCount('courses')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'courses' => $category->courses_count ?? 0,
                ];
            });

        return Inertia::render('Admin/Categories', [
            'categories' => $categories
        ]);
    })->name('admin.categories');

    Route::get('/admin/categories/create', function () {
        $categories = \App\Models\Category::withCount('courses')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'courses' => $category->courses_count ?? 0,
                ];
            });

        return Inertia::render('Admin/Categories', [
            'categories' => $categories
        ]);
    })->name('admin.categories.create');

    Route::get('/admin/categories/{id}/edit', function ($id) {
        $categories = \App\Models\Category::withCount('courses')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'courses' => $category->courses_count ?? 0,
                ];
            });

        return Inertia::render('Admin/Categories', [
            'categories' => $categories
        ]);
    })->name('admin.categories.edit');

    Route::get('/admin/my-courses', function () {
        return Inertia::render('Admin/MyCourses');
    })->name('admin.my-courses');

    Route::get('/admin/my-courses/{code}', function ($code) {
        $course = \App\Models\Course::with(['owner', 'sessions'])
            ->where('code', $code)
            ->first();

        if (!$course) {
            return Inertia::render('Trainer/Courses/Show', [
                'program' => [
                    'id' => $code,
                    'name' => '',
                    'code' => $code,
                    'category' => '',
                    'level' => '',
                    'period' => '',
                    'time' => '',
                    'location' => '',
                    'trainer' => '',
                    'certificated' => '',
                    'status' => '',
                    'description' => '',
                    'image_url' => null,
                    'approval_status' => 'approved',
                ],
            ]);
        }

        // Get instructor/owner information
        $owner = $course->owner;
        $trainerName = $owner?->name ?? '';
        $trainerEmail = $owner?->email ?? '';

        // Count sessions
        $sessionsCount = $course->sessions()->count();

        return Inertia::render('Trainer/Courses/Show', [
            'program' => [
                'id' => $course->id,
                'name' => $course->title,
                'title' => $course->title,
                'code' => $course->code,
                'category' => $course->category,
                'level' => $course->level ?? 'beginner',
                'period' => '',
                'time' => '',
                'location' => '',
                'trainer' => $trainerName,
                'trainer_email' => $trainerEmail,
                'certificated' => '',
                'status' => $course->status,
                'description' => $course->description ?? '',
                'image_url' => $course->thumbnail_path,
                'approval_status' => 'approved',
                'learning_outcomes' => $course->learning_outcomes ?? '',
                'target_audience' => $course->target_audience ?? '',
                'prerequisites' => $course->prerequisites ?? '',
                'additional_info' => $course->additional_info ?? '',
                'min_participants' => $course->min_participants ?? 1,
                'max_participants' => $course->max_participants ?? 20,
                'sessions_count' => $sessionsCount,
                'created_at' => $course->created_at?->format('M d, Y'),
            ],
        ]);
    })->name('admin.my-courses.show');

    Route::get('/admin/attendance', function () {
        return Inertia::render('Admin/Attendance');
    })->name('admin.attendance');

    Route::get('/admin/courses/{courseCode}/sessions/{sessionId}/attendance', function ($courseCode, $sessionId) {
        return Inertia::render('Admin/SessionAttendance', [
            'courseCode' => $courseCode,
            'sessionId' => $sessionId
        ]);
    })->name('admin.attendance.session');

    Route::get('/admin/sessions', function () {
        return Inertia::render('Admin/Sessions/Index');
    })->name('admin.sessions.index');

    Route::get('/admin/feedback', function () {
        return Inertia::render('Admin/Feedback');
    })->name('admin.feedback');

    Route::get('/admin/certificates', function () {
        return Inertia::render('Admin/Certificates');
    })->name('admin.certificates');

    Route::get('/admin/certificate-templates', function () {
        return Inertia::render('Admin/CertificateTemplates/Index');
    })->name('admin.certificate-templates.index');

    Route::get('/admin/certificate-templates/create', function () {
        return Inertia::render('Admin/CertificateTemplates/Create');
    })->name('admin.certificate-templates.create');

    Route::get('/admin/certificate-templates/{id}/edit', function ($id) {
        return Inertia::render('Admin/CertificateTemplates/Edit', [
            'templateId' => $id,
        ]);
    })->name('admin.certificate-templates.edit');


});

Route::middleware(['auth', 'role:trainer,admin'])->group(function () {
    Route::get('/trainer', function () {
        return redirect()->route('trainer.courses.index');
    })->name('trainer.dashboard');

    // Trainer Course Routes
    Route::get('/trainer/courses', function () {
        return Inertia::render('Trainer/Courses/Index', [
            'programs' => []
        ]);
    })->name('trainer.courses.index');

    Route::get('/trainer/courses/{code}', function ($code) {
        $course = \App\Models\Course::with(['owner', 'sessions'])
            ->where('code', $code)
            ->first();

        if (!$course) {
            return Inertia::render('Trainer/Courses/Show', [
                'program' => [
                    'id' => $code,
                    'name' => '',
                    'code' => $code,
                    'category' => '',
                    'level' => '',
                    'period' => '',
                    'time' => '',
                    'location' => '',
                    'trainer' => '',
                    'certificated' => '',
                    'status' => '',
                    'description' => '',
                    'image_url' => null,
                    'approval_status' => 'approved',
                ],
            ]);
        }

        // Get instructor/owner information
        $owner = $course->owner;
        $trainerName = $owner?->name ?? '';
        $trainerEmail = $owner?->email ?? '';

        // Count sessions
        $sessionsCount = $course->sessions()->count();

        return Inertia::render('Trainer/Courses/Show', [
            'program' => [
                'id' => $course->id,
                'name' => $course->title,
                'title' => $course->title,
                'code' => $course->code,
                'category' => $course->category,
                'level' => $course->level ?? 'beginner',
                'period' => '',
                'time' => '',
                'location' => '',
                'trainer' => $trainerName,
                'trainer_email' => $trainerEmail,
                'certificated' => '',
                'status' => $course->status,
                'description' => $course->description ?? '',
                'image_url' => $course->thumbnail_path,
                'approval_status' => 'approved',
                'learning_outcomes' => $course->learning_outcomes ?? '',
                'target_audience' => $course->target_audience ?? '',
                'prerequisites' => $course->prerequisites ?? '',
                'additional_info' => $course->additional_info ?? '',
                'min_participants' => $course->min_participants ?? 1,
                'max_participants' => $course->max_participants ?? 20,
                'sessions_count' => $sessionsCount,
                'created_at' => $course->created_at?->format('M d, Y'),
            ],
        ]);
    })->name('trainer.courses.show');

    Route::get('/trainer/attendance', function () {
        return Inertia::render('Trainer/Attendance');
    })->name('trainer.attendance');

    Route::get('/trainer/courses/{courseCode}/sessions/{sessionId}/attendance', function ($courseCode, $sessionId) {
        return Inertia::render('Trainer/SessionAttendance', [
            'courseCode' => $courseCode,
            'sessionId' => $sessionId
        ]);
    })->name('trainer.attendance.session');

    Route::get('/trainer/sessions', function () {
        return Inertia::render('Trainer/Sessions/Index');
    })->name('trainer.sessions.index');

    Route::get('/trainer/feedback', function () {
        return Inertia::render('Trainer/Feedback');
    })->name('trainer.feedback');



    Route::get('/sessions/{id}/certificates', function ($id) {
        $session = \App\Models\TrainingSession::with(['course'])->findOrFail($id);

        $user = Auth::user();
        $canView = $user->role->name === 'admin' ||
            ($user->role->name === 'trainer' && $session->trainer_id === $user->id);

        if (!$canView) {
            abort(403, 'Unauthorized to view session certificates');
        }

        $certificates = \App\Models\Certificate::with(['user', 'enrollment'])
            ->where('session_id', $id)
            ->orderBy('issued_at', 'desc')
            ->get();

        return Inertia::render('Certificates/SessionCertificates', [
            'sessionId' => $id,
            'session' => $session,
            'certificates' => $certificates,
        ]);
    })->name('sessions.certificates');

    Route::get('/courses/{code}/certificates', function ($code) {
        $course = \App\Models\Course::where('code', $code)->firstOrFail();

        $user = Auth::user();
        $canView = $user->role->name === 'admin' ||
            ($user->role->name === 'trainer' && $course->owner_id === $user->id);

        if (!$canView) {
            abort(403, 'Unauthorized to view course certificates');
        }

        $certificates = \App\Models\Certificate::with(['user', 'session'])
            ->where('course_id', $course->id)
            ->orderBy('issued_at', 'desc')
            ->get();

        return Inertia::render('Certificates/CourseCertificates', [
            'programId' => $course->id,
            'program' => $course, // Passing as program prop to reuse component
            'certificates' => $certificates,
        ]);
    })->name('courses.certificates');
});

Route::middleware(['auth', 'role:trainee'])->group(function () {
    Route::get('/trainee', function () {
        return Inertia::render('Trainee/Dashboard');
    })->name('trainee.dashboard');

    // Trainee Course Catalog Routes
    Route::get('/courses', function () {
        return Inertia::render('Trainee/Courses/Index', [
            'programs' => []
        ]);
    })->name('courses.index');
});

Route::get('/courses/{code}', function ($code) {
    return Inertia::render('Trainee/Courses/Show', [
        'programId' => $code,
    ]);
})->name('courses.show');


require __DIR__ . '/auth.php';

// Trainee Feedback Routes
Route::middleware(['auth', 'role:trainee'])->group(function () {
    Route::get('/trainee/feedback', [\App\Http\Controllers\EvaluationController::class, 'index'])
        ->name('trainee.feedback.index');

    // POST /sessions/{id}/evaluation - Trainee submit feedback
    Route::post('/sessions/{id}/evaluation', [\App\Http\Controllers\EvaluationController::class, 'store'])
        ->name('sessions.evaluation.store');
});

// Admin & Trainer - View session evaluations (API only)
Route::middleware(['auth', 'role:admin,trainer'])->group(function () {
    // GET /api/sessions/{id}/evaluation - View all evaluations for a session
    Route::get('/api/sessions/{id}/evaluation', [\App\Http\Controllers\EvaluationController::class, 'show'])
        ->name('api.sessions.evaluation.show');
});
