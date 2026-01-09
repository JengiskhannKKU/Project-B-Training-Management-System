<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainerRequestController extends Controller
{
    /**
        * List current trainer's requests.
        */
    public function index(Request $request)
    {
        $requests = AdminRequest::where('requester_id', $request->user()->id)
            ->latest()
            ->get();

        return $this->successResponse($requests, 'Requests retrieved successfully');
    }

    /**
     * Submit a course/program request (create or update).
     */
    public function program(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['create', 'update'])],
            'program_id' => ['required_if:action,update', 'nullable', 'integer'],
            'payload' => ['required', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $adminRequest = AdminRequest::create([
            'requester_id' => $request->user()->id,
            'target_type' => 'program',
            'action' => $data['action'],
            'target_id' => $data['program_id'] ?? null,
            'payload' => $data['payload'],
            'status' => 'pending',
            'admin_note' => $data['notes'] ?? null,
        ]);

        return $this->createdResponse($adminRequest, 'Program request submitted for admin review.');
    }

    /**
     * Submit a session request (create, update, delete).
     */
    public function session(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['create', 'update', 'delete'])],
            'program_id' => ['required_if:action,create', 'nullable', 'integer'],
            'session_id' => ['required_if:action,update,delete'],
            'payload' => ['required', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $adminRequest = AdminRequest::create([
            'requester_id' => $request->user()->id,
            'target_type' => 'session',
            'action' => $data['action'],
            'target_id' => $data['session_id'] ?? null,
            'payload' => array_merge(
                ['program_id' => $data['program_id'] ?? null],
                $data['payload']
            ),
            'status' => 'pending',
            'admin_note' => $data['notes'] ?? null,
        ]);

        return $this->createdResponse($adminRequest, 'Session request submitted for admin review.');
    }

    /**
     * Submit a trainee enrollment request (add/remove).
     */
    public function trainee(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['add', 'remove'])],
            'session_id' => ['nullable'],
            'program_id' => ['nullable'],
            'trainee_id' => ['nullable'],
            'payload' => ['required', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        if (empty($data['session_id']) && empty($data['program_id'])) {
            return $this->validationErrorResponse(['session_id' => ['Session or program is required']]);
        }

        $adminRequest = AdminRequest::create([
            'requester_id' => $request->user()->id,
            'target_type' => 'trainee',
            'action' => $data['action'],
            'target_id' => $data['trainee_id'] ?? null,
            'payload' => array_merge(
                [
                    'session_id' => $data['session_id'] ?? null,
                    'program_id' => $data['program_id'] ?? null,
                ],
                $data['payload']
            ),
            'status' => 'pending',
            'admin_note' => $data['notes'] ?? null,
        ]);

        return $this->createdResponse($adminRequest, 'Trainee request submitted for admin review.');
    }
}
