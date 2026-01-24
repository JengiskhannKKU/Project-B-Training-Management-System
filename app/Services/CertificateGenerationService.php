<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\CertificateSequence;
use App\Models\CertificateGenerationBatch;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\TrainingSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CertificateGenerationService
{
    public function __construct(
        private CertificateFileService $fileService
    ) {
    }

    /**
     * Generate certificates for a training session with batch tracking.
     *
     * @param TrainingSession $session
     * @param int $issuerId
     * @param bool $eagerGenerate
     * @param string $language
     * @return CertificateGenerationBatch
     */
    public function generateCertificatesForSession(
        TrainingSession $session,
        int $issuerId,
        bool $eagerGenerate = false,
        string $language = 'th'
    ): CertificateGenerationBatch {
        $enrollments = $this->getEligibleEnrollmentsForSession($session->id);

        // Create batch record
        $batch = CertificateGenerationBatch::create([
            'initiated_by' => $issuerId,
            'type' => 'session',
            'session_id' => $session->id,
            'course_id' => $session->course_id,
            'total_count' => $enrollments->count(),
            'status' => 'processing',
            'started_at' => now(),
        ]);

        DB::beginTransaction();
        try {
            $certificates = $this->generateCertificates(
                enrollments: $enrollments,
                issuerId: $issuerId,
                sessionId: $session->id,
                courseId: $session->course_id,
                trainerIds: [$session->trainer_id],
                language: $language,
                eagerGenerate: $eagerGenerate,
                batch: $batch
            );

            $batch->markAsCompleted();
            DB::commit();

            return $batch->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            $batch->markAsFailed(['message' => $e->getMessage()]);

            Log::error('Certificate batch generation failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate certificates for a course with batch tracking.
     *
     * @param Course $course
     * @param int $issuerId
     * @param bool $eagerGenerate
     * @param string $language
     * @return CertificateGenerationBatch
     */
    public function generateCertificatesForCourse(
        Course $course,
        int $issuerId,
        bool $eagerGenerate = false,
        string $language = 'th'
    ): CertificateGenerationBatch {
        $enrollments = $this->getEligibleEnrollmentsForCourse($course->id);

        // Create batch record
        $batch = CertificateGenerationBatch::create([
            'initiated_by' => $issuerId,
            'type' => 'course',
            'course_id' => $course->id,
            'session_id' => null,
            'total_count' => $enrollments->count(),
            'status' => 'processing',
            'started_at' => now(),
        ]);

        DB::beginTransaction();
        try {
            // For course-level certs, we may need to aggregate trainer IDs from multiple sessions
            $trainerIds = $this->getTrainerIdsForCourse($course->id);

            $certificates = $this->generateCertificates(
                enrollments: $enrollments,
                issuerId: $issuerId,
                sessionId: null,
                courseId: $course->id,
                trainerIds: $trainerIds,
                language: $language,
                eagerGenerate: $eagerGenerate,
                batch: $batch
            );

            $batch->markAsCompleted();
            DB::commit();

            return $batch->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            $batch->markAsFailed(['message' => $e->getMessage()]);

            Log::error('Certificate batch generation failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate certificates from a certificate request.
     *
     * @param CertificateRequest $request
     * @param int $issuerId
     * @param bool $eagerGenerate
     * @param string $language
     * @return CertificateGenerationBatch
     */
    public function generateFromRequest(
        CertificateRequest $request,
        int $issuerId,
        bool $eagerGenerate = false,
        string $language = 'th'
    ): CertificateGenerationBatch {
        if ($request->type === 'session') {
            $session = TrainingSession::findOrFail($request->session_id);
            return $this->generateCertificatesForSession($session, $issuerId, $eagerGenerate, $language);
        } else {
            $course = Course::findOrFail($request->course_id);
            return $this->generateCertificatesForCourse($course, $issuerId, $eagerGenerate, $language);
        }
    }

    /**
     * Core certificate generation logic.
     *
     * @param Collection $enrollments
     * @param int $issuerId
     * @param int|null $sessionId
     * @param int $courseId
     * @param array $trainerIds
     * @param string $language
     * @param bool $eagerGenerate
     * @param CertificateGenerationBatch|null $batch
     * @return Collection
     */
    protected function generateCertificates(
        Collection $enrollments,
        int $issuerId,
        ?int $sessionId,
        int $courseId,
        array $trainerIds,
        string $language = 'th',
        bool $eagerGenerate = false,
        ?CertificateGenerationBatch $batch = null
    ): Collection {
        $certificates = collect();

        foreach ($enrollments as $enrollment) {
            try {
                // Skip if valid certificate already exists
                $existing = Certificate::where('enrollment_id', $enrollment->id)
                    ->where('status', 'valid')
                    ->first();

                if ($existing) {
                    $batch?->incrementFailed('Certificate already exists for enrollment ' . $enrollment->id);
                    continue;
                }

                $certificate = $this->createCertificateRecord(
                    enrollment: $enrollment,
                    issuerId: $issuerId,
                    sessionId: $sessionId,
                    courseId: $courseId,
                    trainerIds: $trainerIds,
                    language: $language
                );

                // Eager generate file if requested
                if ($eagerGenerate) {
                    $this->fileService->generateAndStoreFile($certificate);
                }

                $certificates->push($certificate);
                $batch?->incrementGenerated();
            } catch (\Exception $e) {
                Log::error('Failed to generate certificate', [
                    'enrollment_id' => $enrollment->id,
                    'error' => $e->getMessage(),
                ]);

                $batch?->incrementFailed($e->getMessage());
            }
        }

        return $certificates;
    }

    /**
     * Create a single certificate record with all metadata.
     *
     * @param Enrollment $enrollment
     * @param int $issuerId
     * @param int|null $sessionId
     * @param int $courseId
     * @param array $trainerIds
     * @param string $language
     * @return Certificate
     */
    protected function createCertificateRecord(
        Enrollment $enrollment,
        int $issuerId,
        ?int $sessionId,
        int $courseId,
        array $trainerIds,
        string $language
    ): Certificate {
        $session = $sessionId ? TrainingSession::find($sessionId) : null;
        $course = Course::find($courseId);

        // Calculate total hours
        $totalHours = $this->calculateTotalHours($session);

        // Get description
        $description = $course->description ?? '';

        return Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $enrollment->user_id,
            'course_id' => $courseId,
            'session_id' => $sessionId,
            'description' => $description,
            'total_hours' => $totalHours,
            'trainer_ids' => $trainerIds,
            'issued_by' => $issuerId,
            'issued_at' => now(),
            'certificate_code' => $this->generateCertificateCode(),
            'organization_name' => config('certificates.organization_name', 'KKU'),
            'organization_logo_url' => config('certificates.organization_logo_url'),
            'language' => $language,
            'status' => 'valid',
            'file_mime_type' => 'application/pdf',
        ]);
    }

    /**
     * Generate unique sequential certificate code.
     *
     * @return string
     */
    protected function generateCertificateCode(): string
    {
        $year = now()->year;
        $sequence = CertificateSequence::getNextSequence($year);

        $padding = config('certificates.certificate_numbering.sequence_padding', 4);

        return sprintf('CERT-%d-%0' . $padding . 'd', $year, $sequence);
    }

    /**
     * Calculate total training hours from session.
     *
     * @param TrainingSession|null $session
     * @return int|null
     */
    protected function calculateTotalHours(?TrainingSession $session): ?int
    {
        if (!$session || !$session->start_date || !$session->end_date) {
            return null;
        }

        // Simple calculation: days * 8 hours
        // You can adjust this logic based on your business requirements
        $days = $session->start_date->diffInDays($session->end_date) + 1;
        return $days * 8;
    }

    /**
     * Get eligible enrollments for a session.
     *
     * @param int $sessionId
     * @return Collection
     */
    public function getEligibleEnrollmentsForSession(int $sessionId): Collection
    {
        return Enrollment::query()
            ->where('session_id', $sessionId)
            ->where('status', 'completed')
            ->whereDoesntHave('certificate', function ($query) {
                $query->where('status', 'valid');
            })
            ->with(['user', 'session'])
            ->get();
    }

    /**
     * Get eligible enrollments for a course (one per user).
     *
     * @param int $courseId
     * @return Collection
     */
    public function getEligibleEnrollmentsForCourse(int $courseId): Collection
    {
        // For course-level certificates, we want ONE certificate per user
        // Select the most recent completed enrollment for each user
        $enrollments = Enrollment::query()
            ->where('status', 'completed')
            ->whereHas('session', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->whereDoesntHave('certificate', function ($query) {
                $query->where('status', 'valid');
            })
            ->with(['user', 'session'])
            ->get();

        // Group by user_id and get the most recent enrollment for each user
        $uniqueEnrollments = $enrollments->groupBy('user_id')->map(function ($userEnrollments) {
            return $userEnrollments->sortByDesc('updated_at')->first();
        });

        return $uniqueEnrollments->values();
    }

    /**
     * Get all trainer IDs associated with a course.
     *
     * @param int $courseId
     * @return array
     */
    protected function getTrainerIdsForCourse(int $courseId): array
    {
        return TrainingSession::where('course_id', $courseId)
            ->pluck('trainer_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get certificate generation statistics.
     *
     * @param CertificateGenerationBatch $batch
     * @return array
     */
    public function getBatchStatistics(CertificateGenerationBatch $batch): array
    {
        return [
            'batch_id' => $batch->id,
            'type' => $batch->type,
            'status' => $batch->status,
            'total_count' => $batch->total_count,
            'generated_count' => $batch->generated_count,
            'failed_count' => $batch->failed_count,
            'success_rate' => $batch->success_rate,
            'progress_percentage' => $batch->progress_percentage,
            'started_at' => $batch->started_at?->toIso8601String(),
            'completed_at' => $batch->completed_at?->toIso8601String(),
            'errors' => $batch->errors,
        ];
    }
}
