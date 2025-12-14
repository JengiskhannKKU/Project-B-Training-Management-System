<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:trainer'])->group(function () {
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
                'name' => 'Advanced UX Design Principles',
                'code' => 'PRG-001',
                'category' => 'Design',
                'level' => 'Advanced',
                'period' => 'May 1 - MAY 2',
                'time' => '09:00 - 12:00 AM',
                'location' => 'Smart Classrom',
                'trainer' => 'Natthiya Chakaew',
                'certificated' => 'Standard',
                'status' => 'OPEN',
                'description' => 'A comprehensive course on creating intuitive and beautiful user experience.',
                'image_url' => null,
            ]
        ]);
    })->name('trainer.programs.show');
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
});


require __DIR__.'/auth.php';
