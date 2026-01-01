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
        return Enrollment::query()
            ->where('status', 'completed')
            ->whereHas('session', function ($builder) use ($programId) {
                $builder->where('program_id', $programId);
            })
            ->with('session')
            ->get();
    }

    public function generateCertificatesForSession(TrainingSession $session, int $issuedBy): array
    {
        $enrollments = $this->getEligibleEnrollmentsForSession($session->id);

        return $this->issueCertificates($enrollments, [
            'program_id' => $session->program_id,
            'session_id' => $session->id,
            'issued_by' => $issuedBy,
        ]);
    }

    public function generateCertificatesForProgram(Program $program, int $issuedBy): array
    {
        $enrollments = $this->getEligibleEnrollmentsForProgram($program->id);

        return $this->issueCertificates($enrollments, [
            'program_id' => $program->id,
            'session_id' => null,
            'issued_by' => $issuedBy,
        ]);
    }

    public function getEligibleEnrollmentsForRequest(int $certificateRequestId): Collection
    {
        $request = CertificateRequest::findOrFail($certificateRequestId);

        if ($request->type === 'session') {
            return $this->getEligibleEnrollmentsForSession($request->session_id);
        }

        return $this->getEligibleEnrollmentsForProgram($request->program_id);
    }

    public function generateCertificates(int $certificateRequestId, int $issuedBy): array
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
                Certificate::create([
                    'enrollment_id' => $enrollment->id,
                    ...$payload,
                ]);
                $created++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    private function issueCertificates(Collection $enrollments, array $context): array
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
                Certificate::create([
                    'enrollment_id' => $enrollment->id,
                    ...$payload,
                ]);
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
