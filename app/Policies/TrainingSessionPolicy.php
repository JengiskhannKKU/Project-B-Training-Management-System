<?php

namespace App\Policies;

use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TrainingSessionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TrainingSession $trainingSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TrainingSession $trainingSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TrainingSession $trainingSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TrainingSession $trainingSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TrainingSession $trainingSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can submit evaluation for the session.
     */
    public function submitEvaluation(User $user, TrainingSession $trainingSession): Response
    {
        // 1. Must be a trainee
        if (!$user->isRole('trainee')) {
            return Response::deny('Only trainees can submit evaluations.');
        }

        // 2. Session must be completed
        if ($trainingSession->status !== 'completed') {
            return Response::deny('Evaluations can only be submitted for completed sessions.');
        }

        // 3. Must be enrolled (approved or completed status)
        $enrollment = $trainingSession->enrollments()
            ->where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->first();

        if (!$enrollment) {
            return Response::deny('You are not enrolled in this session.');
        }

        // 4. Must have >= 80% attendance
        // Use attendance_percent from enrollment (calculated by multi-day attendance system)
        $attendanceRate = $enrollment->attendance_percent ?? 0;

        if ($attendanceRate < 80) {
            return Response::deny('You must have at least 80% attendance to submit feedback.');
        }

        // 5. Must not have already submitted evaluation
        $existingEvaluation = \App\Models\Evaluation::where('session_id', $trainingSession->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existingEvaluation) {
            return Response::deny('You have already submitted feedback for this session.');
        }

        return Response::allow();
    }
}
