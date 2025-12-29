<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificateRequest;
use App\Models\Program;
use App\Models\TrainingSession;
use Illuminate\Http\Request;
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
}
