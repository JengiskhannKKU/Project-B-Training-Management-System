<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\SessionDay;
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
            'capacity' => ['required', 'integer', 'min:1'],
            'min_participants' => ['required', 'integer', 'min:1'],
            'trainer_id' => ['required', 'integer', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['scheduled', 'ongoing', 'completed', 'cancelled'])],
            'mode' => ['required', Rule::in(['onsite', 'online', 'hybrid'])],
            'online_link' => ['nullable', 'string', 'max:255'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
            'session_days' => ['required', 'array', 'min:1'],
            'session_days.*.date' => ['required', 'date'],
            'session_days.*.start_time' => ['required', 'date_format:H:i'],
            'session_days.*.end_time' => ['required', 'date_format:H:i', 'after:session_days.*.start_time'],
        ]);

        // Check for conflicts
        foreach ($data['session_days'] as $day) {
            if (TrainingSession::trainerHasConflict($data['trainer_id'], $day['date'], $day['start_time'], $day['end_time'])) {
                return $this->errorResponse('Trainer is already booked at this time on ' . $day['date'], 422);
            }
            if (!empty($data['location']) && TrainingSession::locationHasConflict($data['location'], $day['date'], $day['start_time'], $day['end_time'])) {
                return $this->errorResponse('Location is already booked at this time on ' . $day['date'], 422);
            }
        }

        $session = null;
        \DB::transaction(function () use ($data, &$session) {
            $sessionData = [
                'course_id' => $data['course_id'],
                'title' => $data['title'] ?? null,
                'min_participants' => $data['min_participants'],
                'capacity' => $data['capacity'],
                'registration_start' => $data['registration_start'] ?? null,
                'registration_end' => $data['registration_end'] ?? null,
                'mode' => $data['mode'],
                'online_link' => $data['online_link'] ?? null,
                'trainer_id' => $data['trainer_id'],
                'location' => $data['location'] ?? null,
                'status' => $data['status'] ?? 'scheduled',
            ];

            $session = TrainingSession::create($sessionData);

            // Create session days
            foreach ($data['session_days'] as $index => $day) {
                SessionDay::create([
                    'session_id' => $session->id,
                    'date' => $day['date'],
                    'start_time' => $day['start_time'],
                    'end_time' => $day['end_time'],
                    'day_number' => $index + 1,
                ]);
            }
        });

        return $this->createdResponse($session->load('sessionDays'), 'Session created successfully');
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
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'min_participants' => ['sometimes', 'required', 'integer', 'min:1'],
            'trainer_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['scheduled', 'ongoing', 'completed', 'cancelled'])],
            'mode' => ['sometimes', 'required', Rule::in(['onsite', 'online', 'hybrid'])],
            'online_link' => ['nullable', 'string', 'max:255'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
            'session_days' => ['sometimes', 'required', 'array', 'min:1'],
            'session_days.*.id' => ['nullable', 'integer', 'exists:session_days,id'],
            'session_days.*.date' => ['required', 'date'],
            'session_days.*.start_time' => ['required', 'date_format:H:i'],
            'session_days.*.end_time' => ['required', 'date_format:H:i', 'after:session_days.*.start_time'],
        ]);

        \DB::transaction(function () use ($data, $session) {
            $sessionData = [
                'course_id' => $data['course_id'] ?? $session->course_id,
                'title' => $data['title'] ?? $session->title,
                'min_participants' => $data['min_participants'] ?? $session->min_participants,
                'capacity' => $data['capacity'] ?? $session->capacity,
                'registration_start' => $data['registration_start'] ?? $session->registration_start,
                'registration_end' => $data['registration_end'] ?? $session->registration_end,
                'mode' => $data['mode'] ?? $session->mode,
                'online_link' => $data['online_link'] ?? $session->online_link,
                'trainer_id' => $data['trainer_id'] ?? $session->trainer_id,
                'location' => $data['location'] ?? $session->location,
                'status' => $data['status'] ?? $session->status,
            ];

            $session->update($sessionData);

            // Update session days if provided
            if (isset($data['session_days'])) {
                // Get existing session day IDs
                $existingIds = $session->sessionDays()->pluck('id')->toArray();
                $newIds = [];

                foreach ($data['session_days'] as $index => $day) {
                    if (isset($day['id'])) {
                        // Update existing session day
                        $sessionDay = SessionDay::find($day['id']);
                        if ($sessionDay && $sessionDay->session_id === $session->id) {
                            $sessionDay->update([
                                'date' => $day['date'],
                                'start_time' => $day['start_time'],
                                'end_time' => $day['end_time'],
                                'day_number' => $index + 1,
                            ]);
                            $newIds[] = $sessionDay->id;
                        }
                    } else {
                        // Create new session day
                        $newDay = SessionDay::create([
                            'session_id' => $session->id,
                            'date' => $day['date'],
                            'start_time' => $day['start_time'],
                            'end_time' => $day['end_time'],
                            'day_number' => $index + 1,
                        ]);
                        $newIds[] = $newDay->id;
                    }
                }

                // Delete session days that were removed
                $idsToDelete = array_diff($existingIds, $newIds);
                if (!empty($idsToDelete)) {
                    SessionDay::whereIn('id', $idsToDelete)->delete();
                }
            }
        });

        return $this->successResponse($session->fresh()->load('sessionDays'), 'Session updated successfully');
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
                    ->with('sessionDays')
                    ->orderBy('id', 'desc');
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

                // Collect all session days for date range
                $allSessionDays = collect();
                if ($sessions) {
                    foreach ($sessions as $session) {
                        if ($session->sessionDays) {
                            $allSessionDays = $allSessionDays->concat($session->sessionDays);
                        }
                    }
                }

                $dateRange = 'N/A';
                if ($allSessionDays->isNotEmpty()) {
                    $earliestDate = $allSessionDays->pluck('date')->min();
                    $latestDate = $allSessionDays->pluck('date')->max();

                    if ($earliestDate && $latestDate) {
                        $earliestFormatted = Carbon::parse($earliestDate)->format('M j');
                        $latestFormatted = Carbon::parse($latestDate)->format('M j, Y');
                        $dateRange = $earliestDate === $latestDate
                            ? Carbon::parse($earliestDate)->format('M j, Y')
                            : "{$earliestFormatted} - {$latestFormatted}";
                    }
                }

                // Get most common location
                $locations = $sessions ? $sessions->pluck('location')->filter() : collect();
                $locationCounts = $locations->countBy();
                $mostCommonLocation = $locationCounts->sortDesc()->keys()->first() ?? 'N/A';

                // Get time range from first session day
                $timeRange = 'N/A';
                $firstSessionDay = $allSessionDays->sortBy('date')->first();
                if ($firstSessionDay && $firstSessionDay->start_time && $firstSessionDay->end_time) {
                    $timeRange = substr($firstSessionDay->start_time, 0, 5) . ' - ' . substr($firstSessionDay->end_time, 0, 5);
                }

                return [
                    'id' => $course->id,
                    'code' => $course->code,
                    'request_id' => $adminRequest?->id,
                    'name' => $course->title,
                    'description' => $course->description,
                    'category' => $course->category,
                    'image_url' => $course->thumbnail_path ?? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800',
                    'level' => $course->level,
                    'sessions_count' => $course->sessions_count,
                    'enrolled_count' => $totalEnrolled,
                    'max_participants' => $course->max_participants,
                    'status' => $course->status,
                    'sessions' => $sessions ? $sessions->map(function ($session) {
                        $firstDay = $session->sessionDays?->sortBy('date')->first();
                        return [
                            'id' => $session->id,
                            'name' => $session->title,
                            'title' => $session->title,
                            'date' => $firstDay?->date?->format('M j, Y'),
                            'start_date' => $session->sessionDays?->sortBy('date')->first()?->date,
                            'end_date' => $session->sessionDays?->sortByDesc('date')->first()?->date,
                            'time' => $firstDay && $firstDay->start_time && $firstDay->end_time
                                ? substr($firstDay->start_time, 0, 5) . ' - ' . substr($firstDay->end_time, 0, 5)
                                : 'N/A',
                            'start_time' => $firstDay?->start_time ? substr($firstDay->start_time, 0, 5) : null,
                            'end_time' => $firstDay?->end_time ? substr($firstDay->end_time, 0, 5) : null,
                            'location' => $session->location ?? 'N/A',
                            'capacity' => $session->capacity,
                            'enrolled' => $session->enrollments_count,
                            'status' => $session->status,
                            'session_days' => $session->sessionDays?->map(fn($day) => [
                                'id' => $day->id,
                                'date' => $day->date,
                                'start_time' => substr($day->start_time, 0, 5),
                                'end_time' => substr($day->end_time, 0, 5),
                            ])->values() ?? [],
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
                    ->with('sessionDays')
                    ->orderBy('id', 'desc');
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

                // Collect all session days for date range
                $allSessionDays = collect();
                if ($sessions) {
                    foreach ($sessions as $session) {
                        if ($session->sessionDays) {
                            $allSessionDays = $allSessionDays->concat($session->sessionDays);
                        }
                    }
                }

                $dateRange = 'N/A';
                if ($allSessionDays->isNotEmpty()) {
                    $earliestDate = $allSessionDays->pluck('date')->min();
                    $latestDate = $allSessionDays->pluck('date')->max();

                    if ($earliestDate && $latestDate) {
                        $earliestFormatted = Carbon::parse($earliestDate)->format('M j');
                        $latestFormatted = Carbon::parse($latestDate)->format('M j, Y');
                        $dateRange = $earliestDate === $latestDate
                            ? Carbon::parse($earliestDate)->format('M j, Y')
                            : "{$earliestFormatted} - {$latestFormatted}";
                    }
                }

                // Get most common location
                $locations = $sessions ? $sessions->pluck('location')->filter() : collect();
                $locationCounts = $locations->countBy();
                $mostCommonLocation = $locationCounts->sortDesc()->keys()->first() ?? 'N/A';

                // Get time range from first session day
                $timeRange = 'N/A';
                $firstSessionDay = $allSessionDays->sortBy('date')->first();
                if ($firstSessionDay && $firstSessionDay->start_time && $firstSessionDay->end_time) {
                    $timeRange = substr($firstSessionDay->start_time, 0, 5) . ' - ' . substr($firstSessionDay->end_time, 0, 5);
                }

                return [
                    'id' => $course->id,
                    'request_id' => $adminRequest?->id,
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
                        $firstDay = $session->sessionDays?->sortBy('date')->first();
                        return [
                            'id' => $session->id,
                            'name' => $session->title,
                            'title' => $session->title,
                            'date' => $firstDay?->date?->format('M j, Y'),
                            'start_date' => $session->sessionDays?->sortBy('date')->first()?->date,
                            'end_date' => $session->sessionDays?->sortByDesc('date')->first()?->date,
                            'time' => $firstDay && $firstDay->start_time && $firstDay->end_time
                                ? substr($firstDay->start_time, 0, 5) . ' - ' . substr($firstDay->end_time, 0, 5)
                                : 'N/A',
                            'start_time' => $firstDay?->start_time ? substr($firstDay->start_time, 0, 5) : null,
                            'end_time' => $firstDay?->end_time ? substr($firstDay->end_time, 0, 5) : null,
                            'location' => $session->location ?? 'N/A',
                            'capacity' => $session->capacity,
                            'enrolled' => $session->enrollments_count,
                            'status' => $session->status,
                            'session_days' => $session->sessionDays?->map(fn($day) => [
                                'id' => $day->id,
                                'date' => $day->date,
                                'start_time' => substr($day->start_time, 0, 5),
                                'end_time' => substr($day->end_time, 0, 5),
                            ])->values() ?? [],
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
        $query = TrainingSession::with(['course', 'trainer', 'sessionDays'])
            ->withCount(['enrollments', 'attendances']);

        // Apply filters
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by session dates if provided
        if ($request->filled('date_from')) {
            $query->whereHas('sessionDays', function ($q) use ($request) {
                $q->where('date', '>=', $request->date_from);
            });
        }

        if ($request->filled('date_to')) {
            $query->whereHas('sessionDays', function ($q) use ($request) {
                $q->where('date', '<=', $request->date_to);
            });
        }

        // Order by created date (most recent first)
        $query->orderBy('id', 'desc');

        // Paginate results
        $sessions = $query->paginate(20);

        return $this->successResponse($sessions, 'Sessions retrieved successfully');
    }
}
