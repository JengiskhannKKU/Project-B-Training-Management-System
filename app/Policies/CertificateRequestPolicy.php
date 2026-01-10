<?php

namespace App\Policies;

use App\Models\CertificateRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CertificateRequestPolicy
{
    /**
     * Admin can do everything
     */
    private function isAdmin(User $user): bool
    {
        return $user->isRole('admin');
    }

    /**
     * Check if trainer owns the related session/course
     */
    private function ownsResource(User $user, CertificateRequest $request): bool
    {
        if ($request->type === 'session') {
            return $request->session && $request->session->trainer_id === $user->id;
        }

        if ($request->type === 'course') {
            return $request->course && $request->course->owner_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Trainers and admins can view certificate requests
        return $user->isRole('trainer') || $user->isRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CertificateRequest $certificateRequest): bool
    {
        // Admin can view all
        if ($this->isAdmin($user)) {
            return true;
        }

        // Trainer can view if they own the resource
        if ($user->isRole('trainer') && $this->ownsResource($user, $certificateRequest)) {
            return true;
        }

        // Trainee can view their own request
        if ($certificateRequest->requested_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Trainees can create certificate requests for their own enrollments
        // Trainers can create batch requests
        return true;
    }

    /**
     * Determine whether the user can approve/reject the request
     */
    public function approve(User $user, CertificateRequest $certificateRequest): bool
    {
        // Can only approve pending requests
        if ($certificateRequest->status !== 'pending') {
            return false;
        }

        // Only Admin can approve
        return $this->isAdmin($user);
    }

    /**
     * Same as approve
     */
    public function reject(User $user, CertificateRequest $certificateRequest): bool
    {
        return $this->approve($user, $certificateRequest);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CertificateRequest $certificateRequest): bool
    {
        // Only admins can update requests directly
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CertificateRequest $certificateRequest): bool
    {
        // Only admins can delete requests
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CertificateRequest $certificateRequest): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CertificateRequest $certificateRequest): bool
    {
        return $this->isAdmin($user);
    }
}
