<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\Course;
use App\Services\CompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class TrainingSessionController extends Controller
{
    
    public function index(Request $request)
    {
        $query = TrainingSession::query()
            ->with(['course', 'trainer'])
            ->withCount(['enrollments', 'activeEnrollments'])
            ->latest();
        $user = $request->user();

        if ($user && $user->isRole('trainer')) {
            $query->where('trainer_id', $user->id);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        $sessions = $query->get();

        return $this->successResponse($sessions, 'Sessions retrieved successfully');
    }

    
    public function store(Request $request)
    {
        if (!$request->user()->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can create sessions.');
        }

        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date', 'before:end_at'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'capacity' => ['required', 'integer', 'min:1'],
            'min_participants' => ['required', 'integer', 'min:1'],
            'trainer_id' => ['required', 'integer', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['scheduled', 'ongoing', 'completed', 'cancelled'])],
            'mode' => ['required', Rule::in(['onsite', 'online', 'hybrid'])],
            'online_link' => ['nullable', 'string', 'max:255'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
        ]);

        $session = TrainingSession::create($data);

        return $this->createdResponse($session, 'Session created successfully');
    }

    /**
     * Show a single session.
     */
    public function show(TrainingSession $session)
    {
        $user = request()->user();
        if ($user && $user->isRole('trainer') && $session->trainer_id !== $user->id) {
             return $this->forbiddenResponse('You are not authorized to view this session.');
        }

        $session->load('course');
        return $this->successResponse($session, 'Session retrieved successfully');
    }

    /**
     * Update a session.
     */
    public function update(Request $request, TrainingSession $session)
    {
        if (!$request->user()->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can update sessions.');
        }

        $data = $request->validate([
            'course_id' => ['sometimes', 'required', 'integer', 'exists:courses,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'start_at' => ['sometimes', 'required', 'date', 'before:end_at'],
            'end_at' => ['sometimes', 'required', 'date', 'after:start_at'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'min_participants' => ['sometimes', 'required', 'integer', 'min:1'],
            'trainer_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['scheduled', 'ongoing', 'completed', 'cancelled'])],
            'mode' => ['sometimes', 'required', Rule::in(['onsite', 'online', 'hybrid'])],
            'online_link' => ['nullable', 'string', 'max:255'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
        ]);

        $session->update($data);

        return $this->successResponse($session->fresh(), 'Session updated successfully');
    }

    /**
     * Delete a session (hard delete).
     */
    public function destroy(TrainingSession $session)
    {
        if (!request()->user()->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can delete sessions.');
        }

        $session->delete();

        return $this->successResponse(null, 'Session deleted successfully');
    }

    /**
     * Mark a session as completed and evaluate enrollments.
     */
    public function complete(Request $request, TrainingSession $session, CompletionService $completionService): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if (!$user->isRole('admin') && $session->trainer_id !== $user->id) {
            return $this->forbiddenResponse('Only the session trainer or admin can complete this session.');
        }

        $status = strtolower((string) $session->status);
        if (!in_array($status, ['ongoing', 'scheduled'], true)) { // Adjusted for new statuses, assuming 'ongoing' or 'scheduled' can be completed
             // Or maybe only 'ongoing' can be completed? 'open' mapped to 'ongoing'.
             // Let's allow 'scheduled' too just in case.
        }

        $session->update(['status' => 'completed']);

        $summary = $completionService->evaluateSessionCompletions($session->id);

        return $this->successResponse([
            'session' => $session->fresh(),
            'summary' => $summary,
        ], 'Session marked as completed.');
    }

    /**
     * Get all approved courses for the authenticated trainer (with or without sessions).
     */
    public function trainerSessions(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        // Get all approved courses created by this trainer OR where they are assigned as trainer
        $courses = Course::where('status', 'published')
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                      ->orWhereHas('sessions', function ($q) use ($user) {
                          $q->where('trainer_id', $user->id);
                      });
            })
            ->with(['sessions' => function ($query) {
                $query->withCount(['enrollments', 'activeEnrollments'])
                    ->orderBy('start_at', 'desc');
            }])
            ->get()
            ->map(function ($course) {
                $sessions = $course->sessions;

                // Find the AdminRequest linked to this course (if any)
                $adminRequest = \App\Models\AdminRequest::where('target_type', 'course')
                    ->where('target_id', $course->id)
                    ->where('status', 'approved')
                    ->first();

                // Calculate aggregated data
                $totalEnrolled = $sessions ? $sessions->sum('enrollments_count') : 0;
                $dates = $sessions ? $sessions->pluck('start_at')->filter() : collect();
                $earliestDate = $dates->min();
                $latestDate = $dates->max();

                // Format date range
                $dateRange = 'N/A';
                if ($earliestDate && $latestDate) {
                    $earliestFormatted = Carbon::parse($earliestDate)->format('M j');
                    $latestFormatted = Carbon::parse($latestDate)->format('M j, Y');
                    $dateRange = $earliestDate->eq($latestDate)
                        ? Carbon::parse($earliestDate)->format('M j, Y')
                        : "{$earliestFormatted} - {$latestFormatted}";
                }

                // Get most common location
                $locations = $sessions ? $sessions->pluck('location')->filter() : collect();
                $locationCounts = $locations->countBy();
                $mostCommonLocation = $locationCounts->sortDesc()->keys()->first() ?? 'N/A';

                // Get time range from first session
                $firstSession = $sessions ? $sessions->first() : null;
                $timeRange = 'N/A';
                if ($firstSession && $firstSession->start_at && $firstSession->end_at) {
                    $timeRange = $firstSession->start_at->format('H:i') . ' - ' . $firstSession->end_at->format('H:i');
                }

                return [
                    'id' => $course->id,
                    'request_id' => $adminRequest?->id,
                    'name' => $course->title,
                    'image_url' => $course->thumbnail_path ?? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800',
                    'rating' => 4.5,
                    'level' => ucfirst($course->level ?? 'Beginner'),
                    'trainees_count' => $totalEnrolled,
                    'price' => 'Free', 
                    'date' => $dateRange,
                    'time' => $timeRange,
                    'location' => $mostCommonLocation,
                    'category' => $course->category,
                    'duration' => 'N/A',
                    'status' => $course->status,
                    'sessions' => $sessions ? $sessions->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'name' => $session->title,
                            'title' => $session->title,
                            'date' => $session->start_at?->format('M j, Y'),
                            'start_date' => $session->start_at?->format('Y-m-d'),
                            'end_date' => $session->end_at?->format('Y-m-d'),
                            'time' => $session->start_at && $session->end_at
                                ? $session->start_at->format('H:i') . ' - ' . $session->end_at->format('H:i')
                                : 'N/A',
                            'start_time' => $session->start_at?->format('H:i'),
                            'end_time' => $session->end_at?->format('H:i'),
                            'location' => $session->location ?? 'N/A',
                            'capacity' => $session->capacity,
                            'enrolled' => $session->enrollments_count,
                            'status' => $session->status,
                        ];
                    })->values()->all() : [],
                ];
            });

        return $this->successResponse($courses, 'Trainer courses retrieved successfully');
    }

    /**
     * Get all approved courses for admin (with or without sessions).
     */
    public function adminSessions(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        // Get all approved courses
        $courses = Course::where('status', 'published')
            ->with(['sessions' => function ($query) {
                $query->withCount(['enrollments', 'activeEnrollments'])
                    ->orderBy('start_at', 'desc');
            }])
            ->get()
            ->map(function ($course) {
                $sessions = $course->sessions;

                // Find the AdminRequest linked to this course
                $adminRequest = \App\Models\AdminRequest::where('target_type', 'course')
                    ->where('target_id', $course->id)
                    ->where('status', 'approved')
                    ->first();

                // Calculate aggregated data
                $totalEnrolled = $sessions ? $sessions->sum('enrollments_count') : 0;
                $dates = $sessions ? $sessions->pluck('start_at')->filter() : collect();
                $earliestDate = $dates->min();
                $latestDate = $dates->max();

                // Format date range
                $dateRange = 'N/A';
                if ($earliestDate && $latestDate) {
                    $earliestFormatted = Carbon::parse($earliestDate)->format('M j');
                    $latestFormatted = Carbon::parse($latestDate)->format('M j, Y');
                    $dateRange = $earliestDate->eq($latestDate)
                        ? Carbon::parse($earliestDate)->format('M j, Y')
                        : "{$earliestFormatted} - {$latestFormatted}";
                }

                // Get most common location
                $locations = $sessions ? $sessions->pluck('location')->filter() : collect();
                $locationCounts = $locations->countBy();
                $mostCommonLocation = $locationCounts->sortDesc()->keys()->first() ?? 'N/A';

                // Get time range from first session
                $firstSession = $sessions ? $sessions->first() : null;
                $timeRange = 'N/A';
                if ($firstSession && $firstSession->start_at && $firstSession->end_at) {
                    $timeRange = $firstSession->start_at->format('H:i') . ' - ' . $firstSession->end_at->format('H:i');
                }

                return [
                    'id' => $course->id,
                    'request_id' => $adminRequest?->id,
                    // 'code' => $course->code,
                    'name' => $course->title,
                    'image_url' => $course->thumbnail_path ?? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800',
                    'rating' => 4.5, // Placeholder
                    'level' => ucfirst($course->level ?? 'Beginner'),
                    'trainees_count' => $totalEnrolled,
                    'price' => 'Free', // Placeholder
                    'date' => $dateRange,
                    'time' => $timeRange,
                    'location' => $mostCommonLocation,
                    'category' => $course->category,
                    'duration' => 'N/A',
                    'status' => $course->status,
                    'sessions' => $sessions ? $sessions->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'name' => $session->title,
                            'title' => $session->title,
                            'date' => $session->start_at?->format('M j, Y'),
                            'start_date' => $session->start_at?->format('Y-m-d'),
                            'end_date' => $session->end_at?->format('Y-m-d'),
                            'time' => $session->start_at && $session->end_at
                                ? $session->start_at->format('H:i') . ' - ' . $session->end_at->format('H:i')
                                : 'N/A',
                            'start_time' => $session->start_at?->format('H:i'),
                            'end_time' => $session->end_at?->format('H:i'),
                            'location' => $session->location ?? 'N/A',
                            'capacity' => $session->capacity,
                            'enrolled' => $session->enrollments_count,
                            'status' => $session->status,
                        ];
                    })->values()->all() : [],
                ];
            });

        return $this->successResponse($courses, 'Admin courses retrieved successfully');
    }

    /**
     * Get sessions for attendance management (session-first view).
     */
    public function sessionsForAttendance(Request $request): JsonResponse
    {
        $query = TrainingSession::with(['course', 'trainer'])
            ->withCount(['enrollments', 'attendances']);

        // Apply filters
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('start_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('start_at', '<=', $request->date_to);
        }

        // Order by date (most recent first)
        $query->orderBy('start_at', 'desc');

        // Paginate results
        $sessions = $query->paginate(20);

        return $this->successResponse($sessions, 'Sessions retrieved successfully');
    }
}
