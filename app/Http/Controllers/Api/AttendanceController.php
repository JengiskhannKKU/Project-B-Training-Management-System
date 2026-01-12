<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SessionDay;
use App\Models\TrainingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    protected function statusOptions(): array
    {
        return ['present', 'absent', 'late', 'leave_early'];
    }

    public function enrollmentsForAttendance(TrainingSession $session)
    {
        $enrollments = $session->enrollments()
            ->whereIn('status', ['pending', 'confirmed'])
            ->with(['user.profile', 'user.role', 'attendances'])
            ->get();

        return $this->successResponse($enrollments, 'Enrollments retrieved successfully');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer', 'exists:training_sessions,id'],
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'note' => ['nullable', 'string'],
        ]);

        $session = TrainingSession::findOrFail($data['session_id']);
        if (!$request->user()->isRole('admin') && $session->trainer_id !== $request->user()->id) {
            return $this->forbiddenResponse('You can only record attendance for your own sessions.');
        }

        $enrollment = Enrollment::where('id', $data['enrollment_id'])
            ->where('session_id', $data['session_id'])
            ->first();

        if (!$enrollment) {
            return $this->validationErrorResponse([
                'enrollment_id' => ['Enrollment does not belong to the session.'],
            ]);
        }

        $attendance = Attendance::updateOrCreate(
            ['enrollment_id' => $data['enrollment_id']],
            [
                'session_id' => $data['session_id'],
                'checked_at' => now(),
                'status' => $data['status'],
                'checked_by' => $request->user()->id,
                'note' => $data['note'] ?? null,
            ]
        );

        return $this->createdResponse($attendance->fresh(), 'Attendance recorded successfully');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $session = TrainingSession::find($attendance->session_id);
        if ($session && !$request->user()->isRole('admin') && $session->trainer_id !== $request->user()->id) {
             return $this->forbiddenResponse('You can only update attendance for your own sessions.');
        }

        $data = $request->validate([
            'status' => ['sometimes', 'required', Rule::in($this->statusOptions())],
            'note' => ['nullable', 'string'],
        ]);

        $attendance->update([
            'status' => $data['status'] ?? $attendance->status,
            'note' => array_key_exists('note', $data) ? $data['note'] : $attendance->note,
            'checked_at' => now(),
            'checked_by' => $request->user()->id,
        ]);

        return $this->successResponse($attendance->fresh(), 'Attendance updated successfully');
    }

    public function bulkStore(Request $request, TrainingSession $session)
    {
        if (!$request->user()->isRole('admin') && $session->trainer_id !== $request->user()->id) {
            return $this->forbiddenResponse('You can only record attendance for your own sessions.');
        }

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'items.*.status' => ['required', Rule::in($this->statusOptions())],
            'items.*.note' => ['nullable', 'string'],
        ]);

        $enrollmentIds = collect($data['items'])->pluck('enrollment_id')->unique()->values();
        $validEnrollmentIds = Enrollment::where('session_id', $session->id)
            ->whereIn('id', $enrollmentIds)
            ->pluck('id');

        $invalidIds = $enrollmentIds->diff($validEnrollmentIds);
        if ($invalidIds->isNotEmpty()) {
            return $this->validationErrorResponse([
                'enrollment_id' => ['Some enrollments do not belong to this session.'],
            ]);
        }

        $now = now();
        $userId = $request->user()->id;

        DB::transaction(function () use ($data, $session, $now, $userId) {
            foreach ($data['items'] as $item) {
                Attendance::updateOrCreate(
                    ['enrollment_id' => $item['enrollment_id']],
                    [
                        'session_id' => $session->id,
                        'checked_at' => $now,
                        'status' => $item['status'],
                        'checked_by' => $userId,
                        'note' => $item['note'] ?? null,
                    ]
                );
            }
        });

        return $this->successResponse([
            'count' => count($data['items']),
        ], 'Bulk attendance recorded successfully');
    }

    public function sessionAttendances(TrainingSession $session)
    {
        $attendances = Attendance::with(['enrollment.user'])
            ->where('session_id', $session->id)
            ->get();

        return $this->successResponse($attendances, 'Attendances retrieved successfully');
    }

    public function enrollmentAttendances(Enrollment $enrollment)
    {
        $user = auth()->user();

        // Trainees can only view their own enrollment attendance
        // Trainers and admins can view any enrollment attendance
        if ($user->role_id === 3 && $enrollment->user_id !== $user->id) {
            return $this->forbiddenResponse('You can only view your own attendance records');
        }

        $attendances = Attendance::with('session')
            ->where('enrollment_id', $enrollment->id)
            ->get();

        return $this->successResponse($attendances, 'Attendances retrieved successfully');
    }

    public function attendanceSummary(TrainingSession $session)
    {
        $totalEnrollments = $session->enrollments()
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        $attendances = Attendance::where('session_id', $session->id)->get();

        $summary = [
            'total' => $totalEnrollments,
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'leave_early' => $attendances->where('status', 'leave_early')->count(),
            'not_marked' => $totalEnrollments - $attendances->count(),
        ];

        return $this->successResponse($summary, 'Attendance summary retrieved successfully');
    }

    public function eligibleEnrollments(TrainingSession $session)
    {
        $enrollments = $session->enrollments()
            ->where('status', 'completed')
            ->with(['user', 'session.course'])
            ->get()
            ->filter(function ($enrollment) {
                // Only include enrollments with 80% or higher attendance
                return $enrollment->attendance_percent >= 80;
            });

        return $this->successResponse($enrollments, 'Eligible enrollments retrieved successfully');
    }

    /**
     * Get attendance data for all days of a session (multi-day support)
     * Returns: session info, session_days, enrollments, attendance_matrix
     */
    public function getSessionAttendanceDays(TrainingSession $session)
    {
        $user = request()->user();

        // Authorization: only admin or session trainer
        if (!$user->isRole('admin') && $session->trainer_id !== $user->id) {
            return $this->forbiddenResponse('You can only view attendance for your own sessions.');
        }

        // Load session with related data
        $session->load('course');

        // Get all session days
        $sessionDays = $session->sessionDays()
            ->with(['attendances'])
            ->get()
            ->map(function ($day) {
                return [
                    'id' => $day->id,
                    'date' => $day->date->toDateString(),
                    'start_time' => $day->start_time,
                    'end_time' => $day->end_time,
                    'day_number' => $day->day_number,
                    'status' => $day->status ?? 'active',
                    'has_occurred' => $day->hasOccurred(),
                    'is_today' => $day->isToday(),
                    'attendance_count' => $day->attendances->count(),
                ];
            });

        // Get enrollments for attendance marking (pending or confirmed)
        $enrollments = $session->enrollments()
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('user')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'user_id' => $enrollment->user_id,
                    'session_id' => $enrollment->session_id,
                    'status' => $enrollment->status,
                    'attendance_percent' => $enrollment->attendance_percent ?? 0,
                    'user' => $enrollment->user,
                ];
            });

        // Build attendance matrix: [enrollment_id][session_day_id] = attendance data
        $attendanceMatrix = [];
        foreach ($enrollments as $enrollment) {
            // Access as array since enrollments were mapped to arrays
            $enrollmentId = is_array($enrollment) ? $enrollment['id'] : $enrollment->id;
            $userId = is_array($enrollment) ? $enrollment['user_id'] : $enrollment->user_id;
            $attendanceMatrix[$enrollmentId] = [];

            foreach ($sessionDays as $day) {
                // Find attendance for this user and day
                $attendance = Attendance::where('session_day_id', $day['id'])
                    ->where('user_id', $userId)
                    ->first();

                $attendanceMatrix[$enrollmentId][$day['id']] = [
                    'status' => $attendance ? $attendance->status : 'not_marked',
                    'checked_at' => $attendance ? $attendance->checked_at : null,
                    'note' => $attendance ? $attendance->note : null,
                ];
            }
        }

        return $this->successResponse([
            'session' => $session,
            'session_days' => $sessionDays,
            'enrollments' => $enrollments,
            'attendance_matrix' => $attendanceMatrix,
        ], 'Attendance data retrieved successfully');
    }

    /**
     * Store attendance for a specific day (single record)
     */
    public function storeByDay(Request $request, SessionDay $sessionDay)
    {
        $session = $sessionDay->session;

        // Authorization: only admin or session trainer
        if (!$request->user()->isRole('admin') && $session->trainer_id !== $request->user()->id) {
            return $this->forbiddenResponse('You can only record attendance for your own sessions.');
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'note' => ['nullable', 'string'],
        ]);

        // Verify user is enrolled in this session
        $enrollment = Enrollment::where('session_id', $session->id)
            ->where('user_id', $data['user_id'])
            ->first();

        if (!$enrollment) {
            return $this->validationErrorResponse([
                'user_id' => ['User is not enrolled in this session.'],
            ]);
        }

        $attendance = Attendance::updateOrCreate(
            [
                'session_day_id' => $sessionDay->id,
                'user_id' => $data['user_id'],
            ],
            [
                'session_id' => $session->id,
                'enrollment_id' => $enrollment->id,
                'status' => $data['status'],
                'checked_at' => now(),
                'checked_by' => $request->user()->id,
                'note' => $data['note'] ?? null,
            ]
        );

        return $this->createdResponse($attendance->fresh(), 'Attendance recorded successfully');
    }

    /**
     * Bulk store attendance for a specific day
     */
    public function bulkStoreByDay(Request $request, SessionDay $sessionDay)
    {
        $session = $sessionDay->session;

        // Authorization: only admin or session trainer
        if (!$request->user()->isRole('admin') && $session->trainer_id !== $request->user()->id) {
            return $this->forbiddenResponse('You can only record attendance for your own sessions.');
        }

        $data = $request->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'records.*.status' => ['required', Rule::in($this->statusOptions())],
            'records.*.note' => ['nullable', 'string'],
        ]);

        $userIds = collect($data['records'])->pluck('user_id')->unique();

        // Verify all users are enrolled in this session
        $enrollments = Enrollment::where('session_id', $session->id)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $invalidUsers = $userIds->diff($enrollments->keys());
        if ($invalidUsers->isNotEmpty()) {
            return $this->validationErrorResponse([
                'user_id' => ['Some users are not enrolled in this session.'],
            ]);
        }

        $now = now();
        $checkedBy = $request->user()->id;

        DB::transaction(function () use ($data, $sessionDay, $session, $enrollments, $now, $checkedBy) {
            foreach ($data['records'] as $record) {
                $enrollment = $enrollments[$record['user_id']];

                Attendance::updateOrCreate(
                    [
                        'session_day_id' => $sessionDay->id,
                        'user_id' => $record['user_id'],
                    ],
                    [
                        'session_id' => $session->id,
                        'enrollment_id' => $enrollment->id,
                        'status' => $record['status'],
                        'checked_at' => $now,
                        'checked_by' => $checkedBy,
                        'note' => $record['note'] ?? null,
                    ]
                );
            }
        });

        return $this->successResponse([
            'count' => count($data['records']),
        ], 'Bulk attendance recorded successfully');
    }
}
