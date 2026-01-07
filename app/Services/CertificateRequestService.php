<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CertificateRequestService
{
    public function __construct(
        private CertificateGenerationService $certificateGenerator
    ) {}

    /**
     * Validate if request can be approved
     */
    public function validateRequest(CertificateRequest $request): array
    {
        $checks = [
            'enrollment_completed' => $request->enrollment && $request->enrollment->status === 'completed',
            'session_completed' => $request->type === 'program' ||
                                   ($request->session && $request->session->status === 'completed'),
            'no_existing_certificate' => !$request->enrollment || !Certificate::where('enrollment_id', $request->enrollment_id)
                                                    ->where('status', 'valid')
                                                    ->exists(),
            'template_available' => $this->hasAvailableTemplate($request),
        ];

        return [
            'is_eligible' => !in_array(false, $checks, true),
            'checks' => $checks,
            'warnings' => $this->generateWarnings($checks),
        ];
    }

    /**
     * Approve certificate request and generate certificate
     * This logic is used by BOTH trainer and admin
     */
    public function approve(CertificateRequest $request, User $approver, ?string $note = null): Certificate
    {
        // Validate eligibility
        $validation = $this->validateRequest($request);
        if (!$validation['is_eligible']) {
            throw new \InvalidArgumentException(
                'Certificate request cannot be approved: ' .
                implode(', ', $validation['warnings'])
            );
        }

        return DB::transaction(function () use ($request, $approver, $note) {
            // Update request status
            $request->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'note' => $note,
            ]);

            // Generate certificate using existing service
            $result = $this->certificateGenerator->generateCertificates(
                certificateRequestId: $request->id,
                issuedBy: $approver->id
            );

            // Get the generated certificate
            $certificate = Certificate::where('enrollment_id', $request->enrollment_id)
                                    ->where('status', 'valid')
                                    ->first();

            if (!$certificate) {
                throw new \RuntimeException('Failed to generate certificate');
            }

            return $certificate;
        });
    }

    /**
     * Reject certificate request
     * This logic is used by BOTH trainer and admin
     */
    public function reject(CertificateRequest $request, User $rejector, string $note): CertificateRequest
    {
        if (empty($note) || strlen($note) < 10) {
            throw new \InvalidArgumentException('Rejection note must be at least 10 characters');
        }

        $request->update([
            'status' => 'rejected',
            'approved_by' => $rejector->id,
            'approved_at' => now(),
            'note' => $note,
        ]);

        return $request->fresh();
    }

    /**
     * Get certificate requests with filters
     * Scoped to specific trainer or all (for admin)
     */
    public function getRequests(
        ?User $scopedToTrainer = null,
        ?string $status = null,
        ?string $type = null,
        ?int $programId = null,
        ?int $sessionId = null,
        ?int $trainerId = null,
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = CertificateRequest::query()
            ->with([
                'enrollment.user',
                'session.program',
                'session.trainer',
                'program',
                'program.creator',
                'approver',
                'requester',
            ]);

        // Scope to trainer's resources
        if ($scopedToTrainer && !$scopedToTrainer->isRole('admin')) {
            $query->where(function ($q) use ($scopedToTrainer) {
                $q->whereHas('session', fn($q) => $q->where('trainer_id', $scopedToTrainer->id))
                  ->orWhereHas('program', fn($q) => $q->where('created_by', $scopedToTrainer->id));
            });
        }

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($programId) {
            $query->where('program_id', $programId);
        }

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        if ($trainerId) {
            $query->where(function ($q) use ($trainerId) {
                $q->whereHas('session', fn($q) => $q->where('trainer_id', $trainerId))
                  ->orWhereHas('program', fn($q) => $q->where('created_by', $trainerId));
            });
        }

        if ($search) {
            $query->whereHas('enrollment.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Check if certificate template is available
     */
    private function hasAvailableTemplate(CertificateRequest $request): bool
    {
        // Check session template
        if ($request->type === 'session' && $request->session) {
            $sessionTemplate = CertificateTemplate::where('session_id', $request->session_id)
                ->where('is_active', true)
                ->exists();
            if ($sessionTemplate) {
                return true;
            }
        }

        // Check program template
        if ($request->program) {
            $programTemplate = CertificateTemplate::where('program_id', $request->program_id)
                ->where('is_active', true)
                ->exists();
            if ($programTemplate) {
                return true;
            }
        }

        // Check global template
        return CertificateTemplate::where('scope', 'global')
            ->where('is_active', true)
            ->exists();
    }

    private function generateWarnings(array $checks): array
    {
        $warnings = [];

        if (!$checks['enrollment_completed']) {
            $warnings[] = 'Enrollment is not completed';
        }

        if (!$checks['session_completed']) {
            $warnings[] = 'Session is not completed';
        }

        if (!$checks['no_existing_certificate']) {
            $warnings[] = 'A valid certificate already exists';
        }

        if (!$checks['template_available']) {
            $warnings[] = 'No certificate template available';
        }

        return $warnings;
    }
}
