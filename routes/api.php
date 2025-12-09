<?php

use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\TrainingSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('programs', ProgramController::class);
    Route::apiResource('sessions', TrainingSessionController::class);
});
