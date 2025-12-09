<?php

use App\Http\Controllers\Api\ProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('programs', ProgramController::class);
});
