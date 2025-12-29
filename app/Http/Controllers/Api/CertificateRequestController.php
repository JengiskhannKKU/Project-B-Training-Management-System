<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificateRequest;
use App\Models\Program;
use App\Models\TrainingSession;
use App\Services\CertificateGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CertificateRequestController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->isRole('trainer')) {
            return $this->forbiddenResponse('Only trainers can create certificate requests.');
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(['program', 'session'])],
            'program_id' => ['required_if:type,program', 'nullable', 'integer', 'exists:programs,id'],
            'session_id' => ['required_if:type,session', 'nullable', 'integer', 'exists:training_sessions,id'],
            'note' => ['nullable', 'string'],
            'message' => ['nullable', 'string'],
        ]);

        $note = $data['note'] ?? $data['message'] ?? null;

        if ($data['type'] === 'program') {
            $program = Program::find($data['program_id']);
            if (!$program || $program->approval_status !== 'approved') {
                return $this->validationErrorResponse([
                    'program_id' => ['Program must be approved to request certificates.'],
                ]);
            }

            $duplicate = CertificateRequest::where('type', 'program')
                ->where('program_id', $program->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();
        } else {
            $session = TrainingSession::find($data['session_id']);
            if (!$session || $session->approval_status !== 'approved') {
                return $this->validationErrorResponse([
                    'session_id' => ['Session must be approved to request certificates.'],
                ]);
            }

            if ($session->status !== 'completed') {
                return $this->validationErrorResponse([
                    'session_id' => ['Session must be completed before requesting certificates.'],
                ]);
            }

            $duplicate = CertificateRequest::where('type', 'session')
                ->where('session_id', $session->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();
        }

        if ($duplicate) {
            return $this->validationErrorResponse([
                'type' => ['A pending or approved certificate request already exists for this record.'],
            ]);
        }

        $certificateRequest = CertificateRequest::create([
            'trainer_id' => $user->id,
            'program_id' => $data['type'] === 'program' ? $data['program_id'] : null,
            'session_id' => $data['type'] === 'session' ? $data['session_id'] : null,
            'type' => $data['type'],
            'status' => 'pending',
            'note' => $note,
        ]);

        return $this->createdResponse($certificateRequest, 'Certificate request submitted successfully.');
    }

    public function trainerIndex(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->isRole('trainer')) {
            return $this->forbiddenResponse('Only trainers can view certificate requests.');
        }

        $requests = CertificateRequest::with(['program', 'session', 'approver'])
            ->where('trainer_id', $user->id)
            ->latest()
            ->get();

        return $this->successResponse($requests, 'Certificate requests retrieved successfully.');
    }

    public function adminIndex(Request $request)
    {
        $query = CertificateRequest::with(['trainer', 'program', 'session']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->integer('program_id'));
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->integer('session_id'));
        }

        $requests = $query->latest()->get();

        return $this->successResponse($requests, 'Certificate requests retrieved successfully.');
    }

    public function show(CertificateRequest $certificateRequest, CertificateGenerationService $certificateService)
    {
        $certificateRequest->load(['trainer', 'program', 'session']);

        $eligibleCount = $certificateService
            ->getEligibleEnrollmentsForRequest($certificateRequest->id)
            ->count();

        return $this->successResponse([
            'request' => $certificateRequest,
            'eligible_enrollments_count' => $eligibleCount,
        ], 'Certificate request retrieved successfully.');
    }

    public function approve(Request $request, CertificateRequest $certificateRequest, CertificateGenerationService $certificateService)
    {
        $user = $request->user();

        if (!$user || !$user->isRole('admin')) {
            return $this->forbiddenResponse('Only admins can approve certificate requests.');
        }

        if ($certificateRequest->status !== 'pending') {
            return $this->validationErrorResponse([
                'status' => ['Certificate request must be pending.'],
            ]);
        }

        $targetCheck = $this->validateTargetState($certificateRequest);
        if ($targetCheck !== null) {
            return $targetCheck;
        }

        $result = DB::transaction(function () use ($certificateRequest, $user, $certificateService) {
            $certificateRequest->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $generated = $certificateService->generateCertificates($certificateRequest->id, $user->id);

            return [
                'request' => $certificateRequest->fresh(),
                'generated' => $generated,
            ];
        });

        return $this->successResponse($result, 'Certificate request approved.');
    }

    public function reject(Request $request, CertificateRequest $certificateRequest)
    {
        $user = $request->user();

        if (!$user || !$user->isRole('admin')) {
            return $this->forbiddenResponse('Only admins can reject certificate requests.');
        }

        if ($certificateRequest->status !== 'pending') {
            return $this->validationErrorResponse([
                'status' => ['Certificate request must be pending.'],
            ]);
        }

        $data = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        $certificateRequest->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'note' => $data['note'] ?? $certificateRequest->note,
        ]);

        return $this->successResponse($certificateRequest->fresh(), 'Certificate request rejected.');
    }

    private function validateTargetState(CertificateRequest $certificateRequest)
    {
        if ($certificateRequest->type === 'program') {
            $program = Program::find($certificateRequest->program_id);
            if (!$program || $program->approval_status !== 'approved') {
                return $this->validationErrorResponse([
                    'program_id' => ['Program must be approved to issue certificates.'],
                ]);
            }
        } else {
            $session = TrainingSession::find($certificateRequest->session_id);
            if (!$session || $session->approval_status !== 'approved') {
                return $this->validationErrorResponse([
                    'session_id' => ['Session must be approved to issue certificates.'],
                ]);
            }
            if ($session->status !== 'completed') {
                return $this->validationErrorResponse([
                    'session_id' => ['Session must be completed before issuing certificates.'],
                ]);
            }
        }

        return null;
    }

}
