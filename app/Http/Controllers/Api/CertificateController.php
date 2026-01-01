<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Program;
use App\Models\TrainingSession;
use App\Services\CertificateGenerationService;
use Illuminate\Http\Request;

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

        $isOwner = $certificate->user_id === $user->id;
        $trainerId = $certificate->session?->trainer_id ?? $certificate->enrollment?->session?->trainer_id;
        $isTrainer = $trainerId && $trainerId === $user->id;
        $programOwnerId = $certificate->program?->created_by;
        $isProgramOwner = $programOwnerId && $programOwnerId === $user->id;
        $isAdmin = $user->isRole('admin');

        if (!$isOwner && !$isTrainer && !$isProgramOwner && !$isAdmin) {
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

        if (!$certificate) {
            return $this->notFoundResponse('Certificate not found.');
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

        $result = $certificateService->generateCertificatesForSession($session, $user->id);

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

        $result = $certificateService->generateCertificatesForProgram($program, $user->id);

        return $this->successResponse($result, 'Certificates generated successfully.');
    }
}
