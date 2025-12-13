<?php

use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TrainingSessionController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [AuthController::class, 'register']);

// Public catalog
Route::get('catalog/programs', [CatalogController::class, 'programs']);
Route::get('catalog/programs/{program}/sessions', [CatalogController::class, 'sessions']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [MeController::class, 'show']);
    Route::put('me/profile', [MeController::class, 'updateProfile']);
    Route::post('me/avatar', [MeController::class, 'uploadAvatar']);
    Route::get('me/avatar', [MeController::class, 'showAvatar']);
    Route::get('me/enrollments', [EnrollmentController::class, 'myEnrollments']);
    Route::post('enrollments', [EnrollmentController::class, 'store']);
    Route::put('enrollments/{enrollment}/cancel', [EnrollmentController::class, 'cancel']);

    Route::apiResource('programs', ProgramController::class);
    Route::apiResource('sessions', TrainingSessionController::class);

    Route::middleware('role:admin')->group(function () {
        Route::post('admin/users', [AdminUserController::class, 'store']);
        Route::get('admin/users', [AdminUserController::class, 'index']);
        Route::put('admin/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('admin/users/{user}', [AdminUserController::class, 'destroy']);
        Route::put('admin/users/{user}/deactivate', [AdminUserController::class, 'deactivate']);
        Route::get('users/{user}/avatar', [MeController::class, 'showUserAvatar']);
    });
});
