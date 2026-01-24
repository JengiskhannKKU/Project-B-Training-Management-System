<?php

namespace App\Policies;

use App\Models\Evaluation;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EvaluationPolicy
{
    /**
     * Determine whether the user can view session evaluations.
     * Admin can view all, Trainer can only view their own sessions.
     */
    public function viewSessionEvaluations(User $user, TrainingSession $session): bool
    {
        // Admin can view all evaluations
        if ($user->role->name === 'admin') {
            return true;
        }

        // Trainer can only view evaluations for their own sessions
        if ($user->role->name === 'trainer' && $session->trainer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can submit evaluation for a session.
     * Trainee must have certificate and >= 80% attendance.
     */
    public function submitEvaluation(User $user, TrainingSession $session): bool
    {
        // Only trainees can submit evaluations
        if ($user->role->name !== 'trainee') {
            return false;
        }

        // Must have certificate issued
        $hasCertificate = $session->certificates()
            ->where('user_id', $user->id)
            ->exists();

        if (!$hasCertificate) {
            return false;
        }

        // Must have >= 80% attendance (from enrollment)
        $enrollment = $session->enrollments()
            ->where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->first();

        if (!$enrollment || ($enrollment->attendance_percent ?? 0) < 80) {
            return false;
        }

        // Check if evaluation already submitted
        $alreadySubmitted = Evaluation::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadySubmitted) {
            return false;
        }

        return true;
    }
}
