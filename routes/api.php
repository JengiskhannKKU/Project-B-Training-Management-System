<?php

use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TrainingSessionController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\TrainerRequestController;
use App\Http\Controllers\Api\AdminRequestActionController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\CertificateRequestController;
use App\Http\Controllers\Api\CertificateController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

// Public catalog
Route::get('catalog/programs', [CatalogController::class, 'programs']);
Route::get('catalog/programs/{program}/sessions', [CatalogController::class, 'sessions']);
Route::get('verify/{certificateCode}', [CertificateController::class, 'verify']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [MeController::class, 'show']);
    Route::put('me/profile', [MeController::class, 'updateProfile']);
    Route::post('me/avatar', [MeController::class, 'uploadAvatar']);
    Route::get('me/avatar', [MeController::class, 'showAvatar']);
    Route::delete('me/avatar', [MeController::class, 'deleteAvatar']);
    Route::get('me/enrollments', [EnrollmentController::class, 'myEnrollments']);
    Route::get('me/certificates', [CertificateController::class, 'myCertificates']);
    Route::post('enrollments', [EnrollmentController::class, 'store']);
    Route::put('enrollments/{enrollment}/cancel', [EnrollmentController::class, 'cancel']);
    Route::get('certificates/{certificate}', [CertificateController::class, 'show']);

    // Students can view their own enrollment attendance
    Route::get('enrollments/{enrollment}/attendances', [AttendanceController::class, 'enrollmentAttendances']);

    Route::apiResource('programs', ProgramController::class);
    Route::apiResource('sessions', TrainingSessionController::class);

    Route::middleware('role:trainer,admin')->group(function () {
        Route::post('sessions/{session}/complete', [TrainingSessionController::class, 'complete']);
        Route::get('sessions/{session}/enrollments-for-attendance', [AttendanceController::class, 'enrollmentsForAttendance']);
        Route::get('sessions/{session}/attendances', [AttendanceController::class, 'sessionAttendances']);
        Route::get('sessions/{session}/attendance-summary', [AttendanceController::class, 'attendanceSummary']);
        Route::get('sessions/{session}/eligible-enrollments', [AttendanceController::class, 'eligibleEnrollments']);
        Route::post('sessions/{session}/attendances/bulk', [AttendanceController::class, 'bulkStore']);
        Route::post('attendances', [AttendanceController::class, 'store']);
        Route::put('attendances/{attendance}', [AttendanceController::class, 'update']);
    });

    Route::middleware('role:trainer')->group(function () {
        Route::post('certificate-requests', [CertificateRequestController::class, 'store']);

        Route::prefix('trainer')->group(function () {
            Route::get('requests', [TrainerRequestController::class, 'index']);
            Route::post('program-requests', [TrainerRequestController::class, 'program']);
            Route::post('session-requests', [TrainerRequestController::class, 'session']);
            Route::post('trainee-requests', [TrainerRequestController::class, 'trainee']);
            Route::get('certificate-requests', [CertificateRequestController::class, 'trainerIndex']);
            Route::get('sessions/{session}/certificates', [CertificateController::class, 'trainerSessionCertificates']);
            
            // Image upload for programs
            Route::post('upload/image', [FileUploadController::class, 'image']);
            Route::delete('upload/image', [FileUploadController::class, 'deleteImage']);
        });
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('admin/users', [AdminUserController::class, 'store']);
        Route::get('admin/users', [AdminUserController::class, 'index']);
        Route::put('admin/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('admin/users/{user}', [AdminUserController::class, 'destroy']);
        Route::put('admin/users/{user}/deactivate', [AdminUserController::class, 'deactivate']);
        Route::get('users/{user}/avatar', [MeController::class, 'showUserAvatar']);

        Route::get('admin/requests', [AdminRequestActionController::class, 'index']);
        Route::post('admin/requests/{adminRequest}/approve', [AdminRequestActionController::class, 'approve']);
        Route::post('admin/requests/{adminRequest}/reject', [AdminRequestActionController::class, 'reject']);

        Route::get('admin/certificate-requests', [CertificateRequestController::class, 'adminIndex']);
        Route::get('admin/certificate-requests/{certificateRequest}', [CertificateRequestController::class, 'show']);
        Route::post('admin/certificate-requests/{certificateRequest}/approve', [CertificateRequestController::class, 'approve']);
        Route::post('admin/certificate-requests/{certificateRequest}/reject', [CertificateRequestController::class, 'reject']);
        Route::post('admin/certificates/{certificate}/revoke', [CertificateController::class, 'revoke']);
    });
});
