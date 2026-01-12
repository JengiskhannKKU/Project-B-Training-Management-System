<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\SessionDay;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SessionDayService
{
    /**
     * Generate session days when a session is created or updated.
     * Includes all days (weekends included) per user decision.
     *
     * @param TrainingSession $session
     * @param bool $force Whether to force regeneration even if days already exist
     * @return Collection
     */
    public function generateSessionDays(TrainingSession $session, bool $force = false): Collection
    {
        // Don't regenerate if days already exist (unless forced)
        if (!$force && $session->sessionDays()->exists()) {
            return $session->sessionDays;
        }

        if (!$session->start_at || !$session->end_at) {
            throw new \InvalidArgumentException('Session must have start_at and end_at dates');
        }

        $start = Carbon::parse($session->start_at)->startOfDay();
        $end = Carbon::parse($session->end_at)->startOfDay();

        // If same day, create single day
        if ($start->isSameDay($end)) {
            return $this->createSingleSessionDay($session, $start, Carbon::parse($session->start_at), Carbon::parse($session->end_at));
        }

        // Multi-day session: create days for all dates in range
        return $this->createMultiDaySession($session, $start, $end, Carbon::parse($session->start_at), Carbon::parse($session->end_at));
    }

    /**
     * Update session days when session dates change.
     * Throws exception if attendance exists to prevent data loss.
     *
     * @param TrainingSession $session
     * @return Collection
     * @throws \RuntimeException
     */
    public function updateSessionDays(TrainingSession $session): Collection
    {
        // Check if any attendance has been marked
        $hasAttendance = Attendance::whereHas('sessionDay', function ($query) use ($session) {
            $query->where('session_id', $session->id);
        })->exists();

        if ($hasAttendance) {
            throw new \RuntimeException(
                'Cannot automatically regenerate session days: attendance records exist. ' .
                'Please delete attendance records first or manually adjust session_days table.'
            );
        }

        // Safe to regenerate
        return $this->generateSessionDays($session, true);
    }

    /**
     * Create a single session day for same-day sessions
     *
     * @param TrainingSession $session
     * @param Carbon $date
     * @param Carbon $startDateTime
     * @param Carbon $endDateTime
     * @return Collection
     */
    private function createSingleSessionDay(TrainingSession $session, Carbon $date, Carbon $startDateTime, Carbon $endDateTime): Collection
    {
        DB::transaction(function () use ($session, $date, $startDateTime, $endDateTime) {
            // Clear existing session days
            SessionDay::where('session_id', $session->id)->delete();

            SessionDay::create([
                'session_id' => $session->id,
                'date' => $date,
                'start_time' => $startDateTime->format('H:i:s'),
                'end_time' => $endDateTime->format('H:i:s'),
                'day_number' => 1,
            ]);
        });

        return $session->sessionDays()->get();
    }

    /**
     * Create multiple session days for multi-day sessions
     * Includes all days (weekends included)
     *
     * @param TrainingSession $session
     * @param Carbon $start
     * @param Carbon $end
     * @param Carbon $startDateTime
     * @param Carbon $endDateTime
     * @return Collection
     */
    private function createMultiDaySession(TrainingSession $session, Carbon $start, Carbon $end, Carbon $startDateTime, Carbon $endDateTime): Collection
    {
        DB::transaction(function () use ($session, $start, $end, $startDateTime, $endDateTime) {
            // Clear existing session days
            SessionDay::where('session_id', $session->id)->delete();

            $dayNumber = 1;
            $currentDate = $start->copy();

            // Loop through all days from start to end (includes weekends)
            while ($currentDate->lte($end)) {
                SessionDay::create([
                    'session_id' => $session->id,
                    'date' => $currentDate->toDateString(),
                    'start_time' => $startDateTime->format('H:i:s'),
                    'end_time' => $endDateTime->format('H:i:s'),
                    'day_number' => $dayNumber,
                ]);

                $currentDate->addDay();
                $dayNumber++;
            }
        });

        return $session->sessionDays()->get();
    }

    /**
     * Check if a session can have its days regenerated
     *
     * @param TrainingSession $session
     * @return bool
     */
    public function canRegenerateSessionDays(TrainingSession $session): bool
    {
        $hasAttendance = Attendance::whereHas('sessionDay', function ($query) use ($session) {
            $query->where('session_id', $session->id);
        })->exists();

        return !$hasAttendance;
    }
}
