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
            ->get();

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

        return Inertia::render('Certificates/Show', [
            'certificateId' => $id,
            'certificate' => $certificate,
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
        return Inertia::render('Admin/Categories');
    })->name('admin.categories');

    Route::get('/admin/categories/create', function () {
        return Inertia::render('Admin/Categories');
    })->name('admin.categories.create');

    Route::get('/admin/categories/{id}/edit', function ($id) {
        return Inertia::render('Admin/Categories');
    })->name('admin.categories.edit');

    Route::get('/admin/my-courses', function () {
        return Inertia::render('Admin/MyCourses');
    })->name('admin.my-courses');

    Route::get('/admin/my-courses/{id}', function ($id) {
        $course = \App\Models\Course::with('owner')->find($id);

        if (! $course) {
            return Inertia::render('Trainer/Courses/Show', [
                'program' => [
                    'id' => $id,
                    'name' => '',
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
                    'approval_status' => 'approved', // Admin created courses are auto-approved
                ],
            ]);
        }

        return Inertia::render('Trainer/Courses/Show', [
            'program' => [
                'id' => $course->id,
                'name' => $course->title,
                // 'code' => $course->code, // Code removed
                'category' => $course->category,
                'level' => $course->level ?? '',
                'period' => '', // Duration removed
                'time' => '',
                'location' => '',
                'trainer' => $course->owner?->name ?? '',
                'certificated' => '',
                'status' => $course->status,
                'description' => $course->description ?? '',
                'image_url' => $course->thumbnail_path,
                'approval_status' => 'approved',
                // 'duration_hours' => $course->duration_hours,
            ],
        ]);
    })->name('admin.my-courses.show');

    Route::get('/admin/attendance', function () {
        return Inertia::render('Admin/Attendance');
    })->name('admin.attendance');

    Route::get('/admin/{courseId}/sessions/{sessionId}/attendance', function ($courseId, $sessionId) {
        return Inertia::render('Admin/SessionAttendance', [
            'courseId' => $courseId,
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

    Route::get('/trainer/courses/{id}', function ($id) {
        return Inertia::render('Trainer/Courses/Show', [
            'program' => [
                'id' => $id,
                'name' => '',
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
            ]
        ]);
    })->name('trainer.courses.show');

    Route::get('/trainer/attendance', function () {
        return Inertia::render('Trainer/Attendance');
    })->name('trainer.attendance');

    Route::get('/trainer/{courseId}/sessions/{sessionId}/attendance', function ($courseId, $sessionId) {
        return Inertia::render('Trainer/SessionAttendance', [
            'courseId' => $courseId,
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

    Route::get('/courses/{id}/certificates', function ($id) {
        $course = \App\Models\Course::findOrFail($id);

        $user = Auth::user();
        $canView = $user->role->name === 'admin' ||
            ($user->role->name === 'trainer' && $course->owner_id === $user->id);

        if (!$canView) {
            abort(403, 'Unauthorized to view course certificates');
        }

        $certificates = \App\Models\Certificate::with(['user', 'session'])
            ->where('course_id', $id)
            ->orderBy('issued_at', 'desc')
            ->get();

        return Inertia::render('Certificates/CourseCertificates', [
            'programId' => $id,
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

Route::get('/courses/{id}', function ($id) {
    return Inertia::render('Trainee/Courses/Show', [
        'programId' => $id,
    ]);
})->name('courses.show');


require __DIR__ . '/auth.php';
