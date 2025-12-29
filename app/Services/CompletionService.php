<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\TrainingSession;
use Illuminate\Support\Carbon;

class CompletionService
{
    private const ATTENDED_STATUSES = ['present', 'late'];
    private const MULTI_DAY_THRESHOLD = 0.8;

    public function evaluateEnrollmentCompletion(int $enrollmentId): bool
    {
        $enrollment = Enrollment::with(['session', 'attendances'])
            ->findOrFail($enrollmentId);

        if (!$enrollment->session) {
            return false;
        }

        if ($enrollment->status === 'cancelled') {
            return false;
        }

        $session = $enrollment->session;
        $attendanceCount = $enrollment->attendances()
            ->whereIn('status', self::ATTENDED_STATUSES)
            ->count();

        $totalDays = $this->getSessionDayCount($session);
        $requiredCount = $totalDays <= 1
            ? 1
            : (int) ceil($totalDays * self::MULTI_DAY_THRESHOLD);

        $completed = $attendanceCount >= $requiredCount;

        if ($completed) {
            $enrollment->status = 'completed';
            $enrollment->completed_at = now();
        } else {
            if ($enrollment->status !== 'cancelled') {
                $enrollment->status = 'confirmed';
                $enrollment->completed_at = null;
            }
        }

        $enrollment->save();

        return $completed;
    }

    public function evaluateSessionCompletions(int $sessionId): array
    {
        $enrollments = Enrollment::where('session_id', $sessionId)->pluck('id');

        $completed = 0;
        $total = $enrollments->count();

        foreach ($enrollments as $enrollmentId) {
            if ($this->evaluateEnrollmentCompletion($enrollmentId)) {
                $completed++;
            }
        }

        return [
            'total' => $total,
            'completed' => $completed,
        ];
    }

    private function getSessionDayCount(TrainingSession $session): int
    {
        if (!$session->start_date || !$session->end_date) {
            return 1;
        }

        $start = Carbon::parse($session->start_date)->startOfDay();
        $end = Carbon::parse($session->end_date)->startOfDay();

        if ($end->lessThan($start)) {
            return 1;
        }

        return $start->diffInDays($end) + 1;
    }
}
