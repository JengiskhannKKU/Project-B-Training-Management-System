<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\TrainingSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CertificateGenerationService
{
    public function __construct(
        private CertificateFileService $fileService
    ) {
    }
    public function getEligibleEnrollmentsForSession(int $sessionId): Collection
    {
        return Enrollment::query()
            ->where('session_id', $sessionId)
            ->where('status', 'completed')
            ->with(['user', 'session'])
            ->get();
    }

    public function getEligibleEnrollmentsForProgram(int $programId): Collection
    {
        // For program-level certificates, we want ONE certificate per user
        // Select the most recent completed enrollment for each user
        $enrollments = Enrollment::query()
            ->where('status', 'completed')
            ->whereHas('session', function ($builder) use ($programId) {
                $builder->where('program_id', $programId);
            })
            ->with('session')
            ->get();

        // Group by user_id and get the most recent enrollment for each user
        $uniqueEnrollments = $enrollments->groupBy('user_id')->map(function ($userEnrollments) {
            // Return the most recent enrollment for this user
            return $userEnrollments->sortByDesc('completed_at')->first();
        });

        return $uniqueEnrollments->values();
    }

    public function generateCertificatesForSession(TrainingSession $session, int $issuedBy, bool $eagerGeneration = false): array
    {
        $enrollments = $this->getEligibleEnrollmentsForSession($session->id);

        return $this->issueCertificates($enrollments, [
            'program_id' => $session->program_id,
            'session_id' => $session->id,
            'issued_by' => $issuedBy,
        ], $eagerGeneration);
    }

    public function generateCertificatesForProgram(Program $program, int $issuedBy, bool $eagerGeneration = false): array
    {
        $enrollments = $this->getEligibleEnrollmentsForProgram($program->id);

        return $this->issueCertificates($enrollments, [
            'program_id' => $program->id,
            'session_id' => null,
            'issued_by' => $issuedBy,
        ], $eagerGeneration);
    }

    public function getEligibleEnrollmentsForRequest(int $certificateRequestId): Collection
    {
        $request = CertificateRequest::findOrFail($certificateRequestId);

        if ($request->type === 'session') {
            return $this->getEligibleEnrollmentsForSession($request->session_id);
        }

        return $this->getEligibleEnrollmentsForProgram($request->program_id);
    }

    public function generateCertificates(int $certificateRequestId, int $issuedBy, bool $eagerGeneration = false): array
    {
        $request = CertificateRequest::findOrFail($certificateRequestId);
        $enrollments = $this->getEligibleEnrollmentsForRequest($certificateRequestId);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($enrollments as $enrollment) {
            $certificate = Certificate::where('enrollment_id', $enrollment->id)->first();

            if ($certificate && $certificate->status === 'valid') {
                $skipped++;
                continue;
            }

            $payload = [
                'user_id' => $enrollment->user_id,
                'program_id' => $enrollment->session?->program_id ?? $request->program_id,
                'session_id' => $request->type === 'session' ? $enrollment->session_id : null,
                'issued_by' => $issuedBy,
                'issued_at' => now(),
                'certificate_code' => $this->generateCertificateCode(),
                'file_url' => null,
                'status' => 'valid',
            ];

            if ($certificate) {
                $certificate->update($payload);
                $updated++;
            } else {
                $certificate = Certificate::create([
                    'enrollment_id' => $enrollment->id,
                    ...$payload,
                ]);
                $created++;
            }

            if ($eagerGeneration) {
                $this->fileService->generateAndStoreFile($certificate);
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    private function issueCertificates(Collection $enrollments, array $context, bool $eagerGeneration = false): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($enrollments as $enrollment) {
            $certificate = Certificate::where('enrollment_id', $enrollment->id)->first();

            if ($certificate && $certificate->status === 'valid') {
                $skipped++;
                continue;
            }

            $payload = [
                'user_id' => $enrollment->user_id,
                'program_id' => $context['program_id'] ?? $enrollment->session?->program_id,
                'session_id' => $context['session_id'],
                'issued_by' => $context['issued_by'],
                'issued_at' => now(),
                'certificate_code' => $this->generateCertificateCode(),
                'file_url' => null,
                'status' => 'valid',
                'revoked_by' => null,
                'revoked_at' => null,
                'revoked_note' => null,
            ];

            if ($certificate) {
                $certificate->update($payload);
            } else {
                $certificate = Certificate::create([
                    'enrollment_id' => $enrollment->id,
                    ...$payload,
                ]);
            }

            if ($eagerGeneration) {
                $this->fileService->generateAndStoreFile($certificate);
            }

            $created++;
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    private function generateCertificateCode(): string
    {
        do {
            $code = 'CERT-' . Str::upper(Str::random(10));
        } while (Certificate::where('certificate_code', $code)->exists());

        return $code;
    }
}
