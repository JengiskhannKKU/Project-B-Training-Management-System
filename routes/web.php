<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$redirectToRoleDashboard = function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $role = Auth::user()->role->name ?? 'student';

    return redirect()->route(match ($role) {
        'admin'   => 'admin.dashboard',
        'trainer' => 'trainer.dashboard',
        default   => 'student.dashboard',
    });
};

Route::get('/', $redirectToRoleDashboard);

Route::get('/dashboard', $redirectToRoleDashboard)
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/me/profile', [ProfileController::class, 'showMyProfile'])->name('me.profile');
    Route::put('/me/profile', [ProfileController::class, 'updateMyProfile'])->name('me.profile.update');
    Route::get('/me/enrollments', function () {
        return Inertia::render('Student/Enrollments/Index');
    })->name('me.enrollments');
    Route::get('/me/enrollments/{id}', function ($id) {
        return Inertia::render('Student/Enrollments/Show', [
            'enrollmentId' => $id,
        ]);
    })->name('me.enrollments.show');
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
    return Inertia::render('Trainer/Programs/Show', [
        'program' => [
            'id' => $id,
            'name' => '',
            'code' => '',
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

    Route::get('/admin/feedback', function () {
        return Inertia::render('Admin/Feedback');
    })->name('admin.feedback');

    Route::get('/admin/settings', function () {
        return Inertia::render('Admin/Settings');
    })->name('admin.settings');
});

Route::middleware(['auth', 'role:trainer,admin'])->group(function () {
    Route::get('/trainer', function () {
        return Inertia::render('Trainer/Dashboard');
    })->name('trainer.dashboard');

    // Trainer Program Routes
    Route::get('/trainer/programs', function () {
        return Inertia::render('Trainer/Programs/Index', [
            'programs' => []
        ]);
    })->name('trainer.programs.index');

Route::get('/trainer/programs/{id}', function ($id) {
    return Inertia::render('Trainer/Programs/Show', [
        'program' => [
            'id' => $id,
            'name' => '',
            'code' => '',
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
})->name('trainer.programs.show');

    Route::get('/trainer/attendance', function () {
        return Inertia::render('Trainer/Attendance');
    })->name('trainer.attendance');

    Route::get('/trainer/{courseId}/sessions/{sessionId}/attendance', function ($courseId, $sessionId) {
        return Inertia::render('Trainer/SessionAttendance', [
            'courseId' => $courseId,
            'sessionId' => $sessionId
        ]);
    })->name('trainer.attendance.session');

    Route::get('/trainer/feedback', function () {
        return Inertia::render('Trainer/Feedback');
    })->name('trainer.feedback');

    Route::get('/trainer/settings', function () {
        return Inertia::render('Trainer/Settings');
    })->name('trainer.settings');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student', function () {
        return Inertia::render('Student/Dashboard');
    })->name('student.dashboard');

    // Student Program Catalog Routes
    Route::get('/student/programs', function () {
        return Inertia::render('Student/Programs/Index', [
            'programs' => []
        ]);
    })->name('student.programs.index');

    Route::get('/programs', function () {
        return Inertia::render('Student/Programs/Index', [
            'programs' => []
        ]);
    })->name('programs.index');
});

Route::get('/programs/{id}', function ($id) {
    return Inertia::render('Student/Programs/Show', [
        'programId' => $id,
    ]);
})->name('programs.show');


require __DIR__.'/auth.php';
