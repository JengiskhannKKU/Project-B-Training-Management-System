<?php

use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TrainingSessionController;
use App\Http\Controllers\Api\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('programs', ProgramController::class);
    Route::apiResource('sessions', TrainingSessionController::class);

    Route::middleware('role:admin')->group(function () {
        Route::post('admin/users', [AdminUserController::class, 'store']);
    });
});
