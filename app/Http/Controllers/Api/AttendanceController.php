<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Enrollment;
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
            ->with(['user', 'attendances'])
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
        $attendances = Attendance::with('session')
            ->where('enrollment_id', $enrollment->id)
            ->get();

        return $this->successResponse($attendances, 'Attendances retrieved successfully');
    }
}
