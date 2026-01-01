<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\Program;
use App\Models\TrainingSession;
use App\Services\CertificateFileService;
use App\Services\CertificateGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CertificateController extends Controller
{
    public function myCertificates(Request $request)
    {
        $user = $request->user();

        $certificates = Certificate::with([
            'program:id,name',
            'session:id,title,program_id,start_date,end_date',
            'session.program:id,name',
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return $this->successResponse($certificates, 'Certificates retrieved successfully.');
    }

    public function trainerSessionCertificates(Request $request, TrainingSession $session)
    {
        $user = $request->user();

        if (!$user->isRole('admin') && $session->trainer_id !== $user->id) {
            return $this->forbiddenResponse('Only the session trainer or admin can view certificates.');
        }

        $certificates = Certificate::with(['user:id,name,email', 'program:id,name', 'session:id,title'])
            ->where('session_id', $session->id)
            ->latest()
            ->get();

        return $this->successResponse($certificates, 'Session certificates retrieved successfully.');
    }

    public function programCertificates(Request $request, Program $program)
    {
        $user = $request->user();

        if (!$user->isRole('admin') && $program->created_by !== $user->id) {
            return $this->forbiddenResponse('Only the program owner or admin can view certificates.');
        }

        $certificates = Certificate::with([
            'user:id,name,email',
            'program:id,name',
            'session:id,title,program_id,start_date,end_date',
        ])
            ->where('program_id', $program->id)
            ->latest()
            ->get();

        return $this->successResponse($certificates, 'Program certificates retrieved successfully.');
    }

    public function show(Request $request, Certificate $certificate)
    {
        $user = $request->user();

        $certificate->load([
            'user:id,name,email',
            'program:id,name,created_by',
            'session:id,title,program_id,trainer_id',
            'enrollment.session:id,trainer_id',
        ]);

        if (!$this->canAccessCertificate($user, $certificate)) {
            return $this->forbiddenResponse('You are not allowed to view this certificate.');
        }

        return $this->successResponse($certificate, 'Certificate retrieved successfully.');
    }

    public function verify(string $certificateCode)
    {
        $certificate = Certificate::with([
            'user:id,name',
            'program:id,name',
            'session:id,title',
        ])->where('certificate_code', $certificateCode)->first();

        if (!$certificate || $certificate->status !== 'valid') {
            return $this->errorResponse('Certificate invalid.', 404);
        }

        return $this->successResponse([
            'recipient' => $certificate->user?->name,
            'program' => $certificate->program?->name,
            'session' => $certificate->session?->title,
            'issued_at' => $certificate->issued_at,
            'status' => $certificate->status,
            'certificate_code' => $certificate->certificate_code,
        ], 'Certificate verified successfully.');
    }

    public function revoke(Request $request, Certificate $certificate)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        if ($certificate->status === 'revoked') {
            return $this->successResponse($certificate, 'Certificate already revoked.');
        }

        $certificate->update([
            'status' => 'revoked',
            'revoked_by' => $request->user()->id,
            'revoked_at' => now(),
            'revoked_note' => $data['note'] ?? null,
        ]);

        return $this->successResponse($certificate->fresh(), 'Certificate revoked successfully.');
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

    public function generateForSession(Request $request, TrainingSession $session, CertificateGenerationService $certificateService)
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if (!$user->isRole('admin') && $session->trainer_id !== $user->id) {
            return $this->forbiddenResponse('Only the session trainer or admin can generate certificates.');
        }

        if ($session->status !== 'completed') {
            return $this->validationErrorResponse([
                'status' => ['Session must be completed before generating certificates.'],
            ]);
        }

        $result = DB::transaction(function () use ($session, $user, $certificateService) {
            $this->logAutoCertificateRequest($user->id, 'session', null, $session->id);

            return $certificateService->generateCertificatesForSession($session, $user->id);
        });

        return $this->successResponse($result, 'Certificates generated successfully.');
    }

    public function generateForProgram(Request $request, Program $program, CertificateGenerationService $certificateService)
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if (!$user->isRole('admin') && $program->created_by !== $user->id) {
            return $this->forbiddenResponse('Only the program owner or admin can generate certificates.');
        }

        if ($program->approval_status !== 'approved') {
            return $this->validationErrorResponse([
                'program_id' => ['Program must be approved before generating certificates.'],
            ]);
        }

        if ($program->status !== 'active') {
            return $this->validationErrorResponse([
                'status' => ['Program must be active before generating certificates.'],
            ]);
        }

        $result = DB::transaction(function () use ($program, $user, $certificateService) {
            $this->logAutoCertificateRequest($user->id, 'program', $program->id, null);

            return $certificateService->generateCertificatesForProgram($program, $user->id);
        });

        return $this->successResponse($result, 'Certificates generated successfully.');
    }

    private function loadCertificateAccessRelations(Certificate $certificate): void
    {
        $certificate->loadMissing([
            'program:id,created_by',
            'session:id,trainer_id',
            'enrollment.session:id,trainer_id',
        ]);
    }

    private function canAccessCertificate($user, Certificate $certificate): bool
    {
        $isOwner = $certificate->user_id === $user->id;
        $trainerId = $certificate->session?->trainer_id ?? $certificate->enrollment?->session?->trainer_id;
        $isTrainer = $trainerId && $trainerId === $user->id;
        $programOwnerId = $certificate->program?->created_by;
        $isProgramOwner = $programOwnerId && $programOwnerId === $user->id;
        $isAdmin = $user->isRole('admin');

        return $isOwner || $isTrainer || $isProgramOwner || $isAdmin;
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

    private function logAutoCertificateRequest(int $userId, string $type, ?int $programId, ?int $sessionId): void
    {
        CertificateRequest::create([
            'trainer_id' => $userId,
            'program_id' => $programId,
            'session_id' => $sessionId,
            'type' => $type,
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'note' => 'auto-generated by owner',
        ]);
    }
}
