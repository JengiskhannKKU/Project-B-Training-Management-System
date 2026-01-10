<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    /**
     * Public catalog of published courses.
     */
    public function courses(): JsonResponse
    {
        $courses = Course::query()
            ->has('sessions')
            ->where('status', 'published')
            ->latest()
            ->get();

        return response()->json($courses);
    }

    /**
     * Public catalog of approved + open sessions for a course.
     */
    public function sessions(Course $course): JsonResponse
    {
        $sessions = TrainingSession::query()
            ->select([
                'id',
                'course_id',
                'title',
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'capacity',
                'trainer_id',
                'trainer_name',
                'trainer_photo_url',
                'location',
                'status',
                'approval_status',
            ])
            ->with('trainer:id,name')
            ->withCount([
                'enrollments as active_enrollments_count' => function ($q) {
                    $q->where('status', '!=', 'cancelled');
                },
            ])
            ->where('course_id', $course->id)
            ->where('approval_status', 'approved')
            ->where('status', 'open')
            ->orderByDesc('start_date')
            ->orderByDesc('start_time')
            ->get();

        return response()->json($sessions);
    }
}
