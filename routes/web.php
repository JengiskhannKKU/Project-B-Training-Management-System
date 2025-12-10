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

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');
});

Route::middleware(['auth', 'role:trainer'])->group(function () {
    Route::get('/trainer', function () {
        return Inertia::render('Trainer/Dashboard');
    })->name('trainer.dashboard');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student', function () {
        return Inertia::render('Student/Dashboard');
    })->name('student.dashboard');
});


require __DIR__.'/auth.php';
