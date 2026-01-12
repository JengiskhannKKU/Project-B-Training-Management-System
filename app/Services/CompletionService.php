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
        $enrollment = Enrollment::with(['session.sessionDays', 'attendances'])
            ->findOrFail($enrollmentId);

        if (!$enrollment->session) {
            return false;
        }

        if ($enrollment->status === 'cancelled') {
            return false;
        }

        $session = $enrollment->session;

        // Get total days from session_days if exists, otherwise fall back to calculation
        $totalDays = $session->sessionDays()->count();
        if ($totalDays === 0) {
            $totalDays = $this->getSessionDayCount($session);
        }

        // Count attended days (present or late)
        // For multi-day sessions with session_day_id: count distinct session_day_id
        $attendedDaysWithSessionDay = $enrollment->attendances()
            ->whereIn('status', self::ATTENDED_STATUSES)
            ->whereNotNull('session_day_id')
            ->distinct('session_day_id')
            ->count('session_day_id');

        // For backward compatibility: if no session_day_id attendance exists, use legacy count
        if ($attendedDaysWithSessionDay === 0) {
            $attendanceCount = $enrollment->attendances()
                ->whereIn('status', self::ATTENDED_STATUSES)
                ->whereNull('session_day_id')
                ->count();
        } else {
            $attendanceCount = $attendedDaysWithSessionDay;
        }

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
        if (!$session->start_at || !$session->end_at) {
            return 1;
        }

        $start = Carbon::parse($session->start_at)->startOfDay();
        $end = Carbon::parse($session->end_at)->startOfDay();

        if ($end->lessThan($start)) {
            return 1;
        }

        return $start->diffInDays($end) + 1;
    }

    /**
     * Get the attendance percentage for an enrollment
     *
     * @param int $enrollmentId
     * @return float
     */
    public function getAttendancePercentage(int $enrollmentId): float
    {
        $enrollment = Enrollment::with(['session.sessionDays'])->findOrFail($enrollmentId);

        if (!$enrollment->session) {
            return 0.0;
        }

        $session = $enrollment->session;

        // Get total days
        $totalDays = $session->sessionDays()->count();
        if ($totalDays === 0) {
            $totalDays = $this->getSessionDayCount($session);
        }

        if ($totalDays === 0) {
            return 0.0;
        }

        // Count attended days
        $attendedDaysWithSessionDay = $enrollment->attendances()
            ->whereIn('status', self::ATTENDED_STATUSES)
            ->whereNotNull('session_day_id')
            ->distinct('session_day_id')
            ->count('session_day_id');

        // Backward compatibility
        if ($attendedDaysWithSessionDay === 0) {
            $attendanceCount = $enrollment->attendances()
                ->whereIn('status', self::ATTENDED_STATUSES)
                ->whereNull('session_day_id')
                ->count();
        } else {
            $attendanceCount = $attendedDaysWithSessionDay;
        }

        return ($attendanceCount / $totalDays) * 100;
    }
}
