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
            ->where('status', 'published')
            ->with(['sessions' => function ($query) {
                $query->whereIn('status', ['scheduled', 'ongoing'])
                      ->orderBy('start_at', 'asc'); // Next upcoming first
            }, 'sessions.enrollments', 'reviews'])
            ->withCount('reviews as reviews_count')
            ->withAvg('reviews as rating', 'rating')
            ->latest()
            ->get()
            ->map(function ($course) {
                // Determine display session (next upcoming/ongoing)
                $displaySession = $course->sessions->first();

                // Calculate total trainees across ALL sessions (not just upcoming)
                // We need to eager load all sessions for count? Or use withSum?
                // Course::withSum('sessions as trainees_count', 'enrollments_count')? 
                // But enrollment count is on session via relation. 
                // Easier: load all sessions for count, or separate query. 
                // Let's iterate. Loading all sessions might be heavy if many.
                // But `sessions` relation above is filtered.
                // Let's use `withCount` on course for total enrollments through sessions?
                // Laravel has `withSum`. `withSum('sessions', 'enrollments_count')` doesn't work if `enrollments_count` is an aggregate.
                // We need `withCount(['sessions as trainees_count' => function($q) { ... }])`? No.
                // We need sum of enrollments.
                // `withCount('enrollments')` on Course would require `hasManyThrough`.
                // Let's add `enrollments` HasManyThrough to Course model? No, let's keep it simple.
                // Just use the loaded filtered sessions for now, or maybe all sessions count is important?
                // "Trainees count" usually implies total students ever or current?
                // Let's assume total active enrollments.
                // We can use a subquery select.
                
                $totalTrainees = \App\Models\Enrollment::whereIn('session_id', function($q) use ($course) {
                    $q->select('id')->from('training_sessions')->where('course_id', $course->id);
                })->where('status', '!=', 'cancelled')->count();

                $dateStr = 'No upcoming sessions';
                $timeStr = '-';
                $locationStr = 'TBD';

                if ($displaySession) {
                    if ($displaySession->start_at && $displaySession->end_at) {
                        $start = $displaySession->start_at;
                        $end = $displaySession->end_at;
                        
                        // Format Date: "Jan 5-10, 2026" or "Jan 5, 2026"
                        if ($start->isSameDay($end)) {
                            $dateStr = $start->format('M j, Y');
                        } else {
                            $dateStr = $start->format('M j') . '-' . $end->format('j, Y');
                        }

                        // Format Time: "09:00 - 16:00"
                        $timeStr = $start->format('H:i') . ' - ' . $end->format('H:i');
                    }
                    $locationStr = $displaySession->location ?? $displaySession->online_link ?? 'Online';
                }

                return [
                    'id' => $course->id,
                    'name' => $course->title,
                    'image_url' => $course->thumbnail_path,
                    'category' => $course->category,
                    'level' => ucfirst($course->level ?? 'Beginner'),
                    'trainees_count' => $totalTrainees,
                    'rating' => round($course->rating ?? 0, 1),
                    'reviews_count' => $course->reviews_count,
                    'price' => 'Free',
                    'date' => $dateStr,
                    'time' => $timeStr,
                    'location' => $locationStr,
                    'status' => $course->status,
                ];
            });

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
                'start_at',
                'end_at',
                'capacity',
                'trainer_id',
                'location',
                'online_link',
                'status',
                'mode',
                'registration_start',
                'registration_end'
            ])
            ->with('trainer:id,name')
            ->withCount([
                'enrollments as active_enrollments_count' => function ($q) {
                    $q->where('status', '!=', 'cancelled');
                },
            ])
            ->where('course_id', $course->id)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->orderByDesc('start_at')
            ->get();

        return response()->json($sessions);
    }
}
