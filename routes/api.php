<?php

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

use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CertificateTemplateController;
use App\Http\Controllers\Api\AdminSessionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SessionDayController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

// Public catalog
Route::get('catalog/courses', [CatalogController::class, 'courses']);
Route::get('catalog/courses/{course}/sessions', [CatalogController::class, 'sessions']);
Route::get('catalog/courses/{course}', [CatalogController::class, 'show']);
Route::get('verify/{certificateCode}', [CertificateController::class, 'verify']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [MeController::class, 'show']);
    Route::put('me/profile', [MeController::class, 'updateProfile']);
    Route::post('me/avatar', [MeController::class, 'uploadAvatar']);
    Route::get('me/avatar', [MeController::class, 'showAvatar']);
    Route::delete('me/avatar', [MeController::class, 'deleteAvatar']);
    Route::get('me/enrollments', [EnrollmentController::class, 'myEnrollments']);
    Route::get('me/certificates', [CertificateController::class, 'myCertificates']);
    Route::get('certificates/verify/{certificateCode}', [CertificateController::class, 'verify']);
    Route::post('enrollments', [EnrollmentController::class, 'store']);
    Route::put('enrollments/{enrollment}/cancel', [EnrollmentController::class, 'cancel']);
    Route::get('certificates/{certificate}', [CertificateController::class, 'show']);
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download']);
    Route::get('certificates/{certificate}/view', [CertificateController::class, 'view']);

    // Trainees can view their own enrollment attendance
    Route::get('enrollments/{enrollment}/attendances', [AttendanceController::class, 'enrollmentAttendances']);

    Route::apiResource('courses', \App\Http\Controllers\Api\CourseController::class)->names([
        'index' => 'api.courses.index',
        'store' => 'api.courses.store',
        'show' => 'api.courses.show',
        'update' => 'api.courses.update',
        'destroy' => 'api.courses.destroy',
    ]);
    Route::apiResource('sessions', TrainingSessionController::class)->names([
        'index' => 'api.sessions.index',
        'store' => 'api.sessions.store',
        'show' => 'api.sessions.show',
        'update' => 'api.sessions.update',
        'destroy' => 'api.sessions.destroy',
    ]);

    // Reviews
    Route::get('courses/{course}/reviews', [ReviewController::class, 'courseReviews']);
    Route::get('sessions/{session}/reviews', [ReviewController::class, 'sessionReviews']);
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::put('reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);

    Route::middleware('role:trainer,admin')->group(function () {
        Route::post('sessions/{session}/complete', [TrainingSessionController::class, 'complete']);
        Route::get('sessions/{session}/enrollments-for-attendance', [AttendanceController::class, 'enrollmentsForAttendance']);
        Route::get('sessions/{session}/attendances', [AttendanceController::class, 'sessionAttendances']);
        Route::get('sessions/{session}/attendance-summary', [AttendanceController::class, 'attendanceSummary']);
        Route::get('sessions/{session}/eligible-enrollments', [AttendanceController::class, 'eligibleEnrollments']);
        Route::get('sessions/{session}/certificates', [CertificateController::class, 'trainerSessionCertificates']);
        Route::get('courses/{course}/certificates', [CertificateController::class, 'courseCertificates']);
        Route::post('sessions/{session}/certificates/generate', [CertificateController::class, 'generateForSession']);
        Route::post('courses/{course}/certificates/generate', [CertificateController::class, 'generateForCourse']);
        Route::post('sessions/{session}/attendances/bulk', [AttendanceController::class, 'bulkStore']);
        Route::post('attendances', [AttendanceController::class, 'store']);
        Route::put('attendances/{attendance}', [AttendanceController::class, 'update']);

        // Multi-day attendance routes
        Route::get('sessions/{session}/attendance-days', [AttendanceController::class, 'getSessionAttendanceDays']);
        Route::post('session-days/{sessionDay}/attendance', [AttendanceController::class, 'storeByDay']);
        Route::post('session-days/{sessionDay}/attendance/bulk', [AttendanceController::class, 'bulkStoreByDay']);

        // Session Day CRUD routes
        Route::get('sessions/{session}/session-days', [SessionDayController::class, 'index']);
        Route::post('sessions/{session}/session-days', [SessionDayController::class, 'store']);
        Route::get('sessions/{session}/session-days/{dayId}', [SessionDayController::class, 'show']);
        Route::put('sessions/{session}/session-days/{dayId}', [SessionDayController::class, 'update']);
        Route::delete('sessions/{session}/session-days/{dayId}', [SessionDayController::class, 'destroy']);
        Route::post('sessions/{session}/session-days/reorder', [SessionDayController::class, 'reorder']);
        Route::put('sessions/{session}/session-days/{dayId}/status', [SessionDayController::class, 'updateStatus']);

        // Sessions for attendance (session-first view)
        Route::get('admin/attendance/sessions', [TrainingSessionController::class, 'sessionsForAttendance']);
        Route::get('trainer/attendance/sessions', [TrainingSessionController::class, 'sessionsForAttendance']);
    });

    Route::middleware('role:trainer,admin')->group(function () {
        Route::prefix('trainer')->group(function () {
            Route::get('sessions/{session}/certificates', [CertificateController::class, 'trainerSessionCertificates']);
            Route::get('sessions', [TrainingSessionController::class, 'trainerSessions']);
            Route::get('courses', [\App\Http\Controllers\Api\CourseController::class, 'trainerCourses']);
            Route::apiResource('certificate-templates', CertificateTemplateController::class)
                ->except(['create', 'edit']);

            // Image upload for programs (now courses)
            Route::post('upload/image', [FileUploadController::class, 'image']);
            Route::delete('upload/image', [FileUploadController::class, 'deleteImage']);
        });
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::post('admin/users', [AdminUserController::class, 'store']);
        Route::get('admin/users', [AdminUserController::class, 'index']);
        Route::put('admin/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('admin/users/{user}', [AdminUserController::class, 'destroy']);
        Route::put('admin/users/{user}/deactivate', [AdminUserController::class, 'deactivate']);
        Route::get('users/{user}/avatar', [MeController::class, 'showUserAvatar']);

        Route::get('admin/certificates', [CertificateController::class, 'adminIndex']);
        Route::post('admin/certificates/{certificate}/revoke', [CertificateController::class, 'revoke']);
        Route::apiResource('admin/certificate-templates', CertificateTemplateController::class)
            ->except(['create', 'edit']);

        Route::get('admin/sessions', [TrainingSessionController::class, 'adminSessions']);

        // Image upload for programs
        Route::post('admin/upload/image', [FileUploadController::class, 'image']);
        Route::delete('admin/upload/image', [FileUploadController::class, 'deleteImage']);

        Route::post('admin/sessions', [AdminSessionController::class, 'store']);
        Route::put('admin/sessions/{session}', [AdminSessionController::class, 'update']);

        // Admin request action routes
        Route::get('admin/requests', [AdminRequestActionController::class, 'index']);
        Route::get('admin/requests/pending-count', [AdminRequestActionController::class, 'getPendingCount']);
        Route::post('admin/requests/{adminRequest}/approve', [AdminRequestActionController::class, 'approve']);
        Route::post('admin/requests/{adminRequest}/reject', [AdminRequestActionController::class, 'reject']);

        // Category management routes
        Route::apiResource('admin/categories', CategoryController::class)->names([
            'index' => 'api.admin.categories.index',
            'store' => 'api.admin.categories.store',
            'show' => 'api.admin.categories.show',
            'update' => 'api.admin.categories.update',
            'destroy' => 'api.admin.categories.destroy',
        ]);
    });
});
