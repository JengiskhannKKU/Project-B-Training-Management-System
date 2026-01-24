<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateGenerationBatch;
use App\Models\CertificateRequest;
use App\Models\CertificateVerificationLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\TrainingSession;
use App\Services\CertificateFileService;
use App\Services\CertificateGenerationService;
use App\Services\CertificateRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CertificateController extends Controller
{
    public function myCertificates(Request $request)
    {
        $user = $request->user();

        $certificates = Certificate::with([
            'course:id,title',
            'session:id,title,course_id,start_date,end_date',
            'session.course:id,title',
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->makeHidden(['file_data', 'background_image'])
            ->map(function ($certificate) {
                $hasEvaluation = $this->hasCompletedEvaluation($certificate);

                return array_merge($certificate->toArray(), [
                    'has_evaluation' => $hasEvaluation,
                    'can_download' => $hasEvaluation,
                ]);
            });

        return $this->successResponse($certificates, 'Certificates retrieved successfully.');
    }

    public function trainerSessionCertificates(Request $request, TrainingSession $session)
    {
        $user = $request->user();

        if (!$user->isRole('admin') && $session->trainer_id !== $user->id) {
            return $this->forbiddenResponse('Only the session trainer or admin can view certificates.');
        }

        $certificates = Certificate::with([
            'user:id,name,email',
            'course:id,title',
            'session:id,title',
            'enrollment:id,status',
        ])
            ->where('session_id', $session->id)
            ->latest()
            ->get()
            ->makeHidden(['file_data', 'background_image']);

        return $this->successResponse($certificates, 'Session certificates retrieved successfully.');
    }

    public function courseCertificates(Request $request, Course $course)
    {
        $user = $request->user();

        if (!$user->isRole('admin') && $course->owner_id !== $user->id) {
            return $this->forbiddenResponse('Only the course owner or admin can view certificates.');
        }

        $certificates = Certificate::with([
            'user:id,name,email',
            'course:id,title',
            'session:id,title,course_id,start_date,end_date',
            'enrollment:id,status',
        ])
            ->where('course_id', $course->id)
            ->latest()
            ->get()
            ->makeHidden(['file_data', 'background_image']);

        return $this->successResponse($certificates, 'Course certificates retrieved successfully.');
    }

    public function adminIndex(Request $request)
    {
        $query = Certificate::with([
            'user:id,name,email',
            'course:id,title',
            'session:id,title,course_id',
            'issuer:id,name',
            'enrollment:id,status',
        ])->latest();

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->integer('session_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('issued_by')) {
            $query->where('issued_by', $request->integer('issued_by'));
        }

        $certificates = $query->get()->makeHidden(['file_data', 'background_image']);

        return $this->successResponse($certificates, 'Certificates retrieved successfully.');
    }

    public function show(Request $request, Certificate $certificate)
    {
        $user = $request->user();

        $certificate->load([
            'user:id,name,email',
            'course:id,title,owner_id',
            'session:id,title,course_id,trainer_id',
            'enrollment.session:id,trainer_id',
        ]);

        if (!$this->canAccessCertificate($user, $certificate)) {
            return $this->forbiddenResponse('You are not allowed to view this certificate.');
        }

        $certificate->makeHidden(['file_data', 'background_image']);

        return $this->successResponse($certificate, 'Certificate retrieved successfully.');
    }

    public function verify(Request $request, string $certificateCode)
    {
        $certificate = Certificate::with([
            'user:id,name',
            'course:id,title',
            'session:id,title',
        ])->where('certificate_code', $certificateCode)->first();

        if (!$certificate) {
            return response()->json([
                'data' => [
                    'certificate_code' => $certificateCode,
                    'is_valid' => false,
                    'status' => 'not_found',
                ],
            ], 404);
        }

        // Log verification attempt
        CertificateVerificationLog::logVerification(
            certificateId: $certificate->id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse([
            'certificate_code' => $certificate->certificate_code,
            'is_valid' => $certificate->status === 'valid',
            'status' => $certificate->status,
            'holder_name' => $certificate->user?->name,
            'course' => $certificate->course?->title,
            'session' => $certificate->session?->title,
            'issued_at' => $certificate->issued_at,
            'organization' => $certificate->organization_name,
            'revoked_at' => $certificate->revoked_at,
        ]);
    }

    public function revoke(Request $request, Certificate $certificate)
    {
        if (!$request->user()->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can revoke certificates.');
        }

        $data = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        if ($certificate->status === 'revoked') {
            $certificate->makeHidden(['file_data', 'background_image']);
            return $this->successResponse($certificate, 'Certificate already revoked.');
        }

        $certificate->update([
            'status' => 'revoked',
            'revoked_by' => $request->user()->id,
            'revoked_at' => now(),
            'revoked_note' => $data['note'] ?? null,
        ]);

        $revoked = $certificate->fresh();
        $revoked->makeHidden(['file_data', 'background_image']);

        return $this->successResponse($revoked, 'Certificate revoked successfully.');
    }

    public function download(Request $request, Certificate $certificate, CertificateFileService $fileService)
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $this->loadCertificateAccessRelations($certificate);

        if (!$this->canAccessCertificate($user, $certificate)) {
            return $this->forbiddenResponse('You are not allowed to download this certificate.');
        }

        // Check if trainee has completed evaluation (only for certificate owners who are trainees)
        if ($certificate->user_id === $user->id && $user->isRole('trainee')) {
            $hasEvaluation = $this->hasCompletedEvaluation($certificate);

            if (!$hasEvaluation) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must complete the course evaluation before downloading your certificate.',
                    'data' => [
                        'requires_evaluation' => true,
                        'session_id' => $certificate->session_id,
                        'evaluation_url' => $certificate->session_id
                            ? route('trainee.feedback.index')
                            : null,
                    ],
                ], 403);
            }
        }

        try {
            $certificate = $fileService->generateAndStoreFile($certificate);
        } catch (RuntimeException $exception) {
            return $this->errorResponse('Certificate file not available.', 500);
        }

        if (!$certificate->file_data) {
            return $this->errorResponse('Certificate file not available.', 500);
        }

        return $this->buildCertificateFileResponse($certificate, 'attachment');
    }

    public function view(Request $request, Certificate $certificate, CertificateFileService $fileService)
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $this->loadCertificateAccessRelations($certificate);

        if (!$this->canAccessCertificate($user, $certificate)) {
            return $this->forbiddenResponse('You are not allowed to view this certificate.');
        }

        try {
            $certificate = $fileService->generateAndStoreFile($certificate);
        } catch (RuntimeException $exception) {
            return $this->errorResponse('Certificate file not available.', 500);
        }

        if (!$certificate->file_data) {
            return $this->errorResponse('Certificate file not available.', 500);
        }

        return $this->buildCertificateFileResponse($certificate, 'inline');
    }

    /**
     * Preview certificate before generation
     */
    public function preview(Request $request, CertificateRenderer $renderer)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with(['user', 'session.course'])->findOrFail($validated['enrollment_id']);

        // Check permissions
        $user = $request->user();
        $session = $enrollment->session;

        if (!$user->isRole('admin') && $session->trainer_id !== $user->id && $session->course->owner_id !== $user->id) {
            return $this->forbiddenResponse('You are not allowed to preview this certificate.');
        }

        // Create temporary certificate (don't save to DB)
        $tempCertificate = new Certificate([
            'enrollment_id' => $enrollment->id,
            'user_id' => $enrollment->user_id,
            'course_id' => $session->course_id,
            'session_id' => $session->id,
            'description' => $session->course->description,
            'total_hours' => 40, // Sample
            'trainer_ids' => [$session->trainer_id],
            'issued_by' => $user->id,
            'issued_at' => now(),
            'certificate_code' => 'CERT-PREVIEW-XXXX',
            'organization_name' => config('certificates.organization_name'),
            'language' => 'en',
            'status' => 'valid',
        ]);

        // Manually set relationships for preview
        $tempCertificate->setRelation('user', $enrollment->user);
        $tempCertificate->setRelation('course', $session->course);
        $tempCertificate->setRelation('session', $session);
        $tempCertificate->setRelation('issuer', $user);

        try {
            // Render PDF
            $pdfData = $renderer->render($tempCertificate);

            // Check for warnings
            $warnings = $renderer->validateTextLength($tempCertificate);

            return response($pdfData, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="preview.pdf"')
                ->header('X-Certificate-Warnings', json_encode($warnings));
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate preview: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Batch generate certificates with progress tracking
     */
    public function generateBatch(Request $request, CertificateGenerationService $certificateService)
    {
        $user = $request->user();

        if (!$user->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can generate certificates.');
        }

        $validated = $request->validate([
            'type' => 'required|in:course,session',
            'course_id' => 'required_if:type,course|exists:courses,id',
            'session_id' => 'required_if:type,session|exists:training_sessions,id',
            'eager_generate' => 'nullable|boolean',
        ]);

        try {
            if ($validated['type'] === 'session') {
                $session = TrainingSession::findOrFail($validated['session_id']);

                if ($session->status !== 'completed') {
                    return $this->validationErrorResponse([
                        'status' => ['Session must be completed before generating certificates.'],
                    ]);
                }

                $batch = $certificateService->generateCertificatesForSession(
                    session: $session,
                    issuerId: $user->id,
                    eagerGenerate: $validated['eager_generate'] ?? false,
                    language: 'en'
                );
            } else {
                $course = Course::findOrFail($validated['course_id']);

                if ($course->status !== 'published') {
                    return $this->validationErrorResponse([
                        'status' => ['Course must be published before generating certificates.'],
                    ]);
                }

                $batch = $certificateService->generateCertificatesForCourse(
                    course: $course,
                    issuerId: $user->id,
                    eagerGenerate: $validated['eager_generate'] ?? false,
                    language: 'en'
                );
            }

            $stats = $certificateService->getBatchStatistics($batch);

            return $this->successResponse($stats, "Generated {$batch->generated_count} certificates");
        } catch (\Exception $e) {
            return $this->errorResponse('Batch generation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get batch generation status
     */
    public function getBatchStatus(Request $request, int $batchId, CertificateGenerationService $certificateService)
    {
        $user = $request->user();

        if (!$user->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can view batch status.');
        }

        $batch = CertificateGenerationBatch::findOrFail($batchId);

        $stats = $certificateService->getBatchStatistics($batch);

        return $this->successResponse($stats);
    }

    public function generateForSession(Request $request, TrainingSession $session, CertificateGenerationService $certificateService)
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if (!$user->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can generate certificates.');
        }

        if ($session->status !== 'completed') {
            return $this->validationErrorResponse([
                'status' => ['Session must be completed before generating certificates.'],
            ]);
        }

        $eagerGeneration = $request->boolean('eager_generation', false);

        $result = DB::transaction(function () use ($session, $user, $certificateService, $eagerGeneration) {
            $this->logAutoCertificateRequest($user->id, 'session', null, $session->id);

            return $certificateService->generateCertificatesForSession($session, $user->id, $eagerGeneration);
        });

        return $this->successResponse($result, 'Certificates generated successfully.');
    }

    public function generateForCourse(Request $request, Course $course, CertificateGenerationService $certificateService)
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if (!$user->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can generate certificates.');
        }

        // Check course status instead of approval_status (as admin creates it)
        if ($course->status !== 'published') {
            return $this->validationErrorResponse([
                'status' => ['Course must be published before generating certificates.'],
            ]);
        }

        $eagerGeneration = $request->boolean('eager_generation', false);

        $result = DB::transaction(function () use ($course, $user, $certificateService, $eagerGeneration) {
            $this->logAutoCertificateRequest($user->id, 'course', $course->id, null);

            return $certificateService->generateCertificatesForCourse($course, $user->id, $eagerGeneration);
        });

        return $this->successResponse($result, 'Certificates generated successfully.');
    }

    private function loadCertificateAccessRelations(Certificate $certificate): void
    {
        $certificate->loadMissing([
            'course:id,owner_id',
            'session:id,trainer_id',
            'enrollment.session:id,trainer_id',
        ]);
    }

    private function canAccessCertificate($user, Certificate $certificate): bool
    {
        $isOwner = $certificate->user_id === $user->id;
        $trainerId = $certificate->session?->trainer_id ?? $certificate->enrollment?->session?->trainer_id;
        $isTrainer = $trainerId && $trainerId === $user->id;
        $courseOwnerId = $certificate->course?->owner_id;
        $isCourseOwner = $courseOwnerId && $courseOwnerId === $user->id;
        $isAdmin = $user->isRole('admin');

        return $isOwner || $isTrainer || $isCourseOwner || $isAdmin;
    }

    private function buildCertificateFileResponse(Certificate $certificate, string $disposition)
    {
        $mimeType = $certificate->file_mime_type ?: 'application/octet-stream';
        $filename = $this->resolveCertificateFilename($certificate, $mimeType);

        return response($certificate->file_data, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
        ]);
    }

    private function resolveCertificateFilename(Certificate $certificate, string $mimeType): string
    {
        $extension = $this->mimeToExtension($mimeType);
        $code = $certificate->certificate_code ?: 'certificate';

        return "certificate-{$code}.{$extension}";
    }

    private function mimeToExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/jpeg', 'image/jpg' => 'jpg',
            default => 'bin',
        };
    }

    private function logAutoCertificateRequest(int $userId, string $type, ?int $courseId, ?int $sessionId): void
    {
        CertificateRequest::create([
            'trainer_id' => $userId,
            'course_id' => $courseId,
            'session_id' => $sessionId,
            'type' => $type,
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'note' => 'auto-generated by owner',
        ]);
    }

    /**
     * Check if trainee has completed evaluation for this certificate's session
     */
    private function hasCompletedEvaluation(Certificate $certificate): bool
    {
        // If certificate is not associated with a session, no evaluation required
        if (!$certificate->session_id) {
            return true;
        }

        // Check if evaluation exists for this user and session
        return Evaluation::where('session_id', $certificate->session_id)
            ->where('user_id', $certificate->user_id)
            ->exists();
    }
}
