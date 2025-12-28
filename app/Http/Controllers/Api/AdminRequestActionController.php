<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminRequest;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Role;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminRequestActionController extends Controller
{
    public function index()
    {
        $requests = AdminRequest::with('requester')->latest()->get();

        return $this->successResponse($requests, 'Requests retrieved successfully');
    }

    public function reject(Request $request, AdminRequest $adminRequest)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string'],
        ]);

        $adminRequest->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'] ?? null,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        // If this is a program request with a linked program, mark the program as rejected
        if ($adminRequest->target_type === 'program' && $adminRequest->target_id) {
            Program::where('id', $adminRequest->target_id)->update([
                'approval_status' => 'rejected',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'approval_note' => $data['admin_note'] ?? null,
                'status' => 'inactive',
            ]);
        }

        // If this is a session request with a linked session, mark the session as closed/rejected instead of deleting/duplicating
        if ($adminRequest->target_type === 'session' && $adminRequest->target_id) {
            TrainingSession::where('id', $adminRequest->target_id)->update([
                'approval_status' => 'rejected',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'status' => 'closed',
            ]);
        }

        return $this->successResponse($adminRequest, 'Request rejected');
    }

    public function approve(Request $request, AdminRequest $adminRequest)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string'],
        ]);

        $adminRequest->admin_note = $data['admin_note'] ?? null;

        // Allow approving previously rejected requests, block already-approved ones
        if ($adminRequest->status === 'approved') {
            return $this->validationErrorResponse(
                ['status' => ['Request already approved.']],
                'Request already approved.'
            );
        }

        return match ($adminRequest->target_type) {
            'program' => $this->approveProgram($request, $adminRequest),
            'session' => $this->approveSession($request, $adminRequest),
            'trainee' => $this->approveTrainee($request, $adminRequest),
            default => $this->validationErrorResponse(['target_type' => ['Unsupported request type']]),
        };
    }

    protected function approveProgram(Request $request, AdminRequest $adminRequest)
    {
        $payload = $adminRequest->payload ?? [];

        return DB::transaction(function () use ($request, $adminRequest, $payload) {
            $programData = [
                'name' => $payload['title'] ?? $payload['name'] ?? 'Untitled Program',
                'code' => $payload['code'] ?? 'PRG-' . strtoupper(Str::random(6)),
                'description' => $payload['full_description'] ?? $payload['short_description'] ?? null,
                'category' => $payload['category'] ?? 'General',
                'level' => $payload['level'] ?? 'beginner',
                'duration_hours' => $payload['duration_hours'] ?? 1,
                'image_url' => $payload['image_url'] ?? null,
                'created_by' => $adminRequest->requester_id,
                'approval_status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'status' => 'active',
                'approval_note' => $adminRequest->admin_note,
            ];

            $program = null;

            if ($adminRequest->action === 'create') {
                if ($adminRequest->target_id) {
                    $program = Program::find($adminRequest->target_id);
                }

                if (!$program) {
                    $program = Program::create($programData);
                    $adminRequest->target_id = $program->id;
                } else {
                    $program->update($programData);
                }
            } else {
                $program = Program::find($adminRequest->target_id);
                if (!$program) {
                    return $this->notFoundResponse('Program not found');
                }
                $program->update($programData);
            }

            $adminRequest->status = 'approved';
            $adminRequest->resolved_by = $request->user()->id;
            $adminRequest->resolved_at = now();
            $adminRequest->save();

            return $this->successResponse($adminRequest->fresh(), 'Program request approved');
        });
    }

    protected function approveSession(Request $request, AdminRequest $adminRequest)
    {
        $payload = $adminRequest->payload ?? [];

        $programId = $payload['program_id'] ?? null;
        if (!$programId) {
            return $this->validationErrorResponse(['program_id' => ['Program is required for sessions']]);
        }

        // Resolve program. Sometimes the payload may carry the program request id instead of the program id.
        $program = Program::find($programId);
        if (!$program && $programId) {
            $maybeProgramRequest = AdminRequest::where('id', $programId)
                ->where('target_type', 'program')
                ->first();
            if ($maybeProgramRequest && $maybeProgramRequest->target_id) {
                $program = Program::find($maybeProgramRequest->target_id);
                if (!$program) {
                    $programPayload = $maybeProgramRequest->payload ?? [];
                    $programData = [
                        'name' => $programPayload['title'] ?? $programPayload['name'] ?? 'Untitled Program',
                        'code' => $programPayload['code'] ?? 'PRG-' . strtoupper(Str::random(6)),
                        'description' => $programPayload['full_description'] ?? $programPayload['short_description'] ?? null,
                        'category' => $programPayload['category'] ?? 'General',
                        'level' => $programPayload['level'] ?? 'beginner',
                        'duration_hours' => $programPayload['duration_hours'] ?? 1,
                        'image_url' => $programPayload['image_url'] ?? null,
                        'created_by' => $maybeProgramRequest->requester_id,
                        'approval_status' => 'approved',
                        'approved_by' => $request->user()->id,
                        'approved_at' => now(),
                        'status' => 'active',
                        'approval_note' => $maybeProgramRequest->admin_note,
                    ];
                    $program = Program::create($programData);
                    $maybeProgramRequest->update([
                        'target_id' => $program->id,
                        'status' => 'approved',
                        'resolved_by' => $request->user()->id,
                        'resolved_at' => now(),
                    ]);
                }

                // Update payload to the real program id for future attempts
                $payload['program_id'] = $program->id;
                $adminRequest->payload = $payload;
                $adminRequest->save();
                $programId = $program->id;
            }
        }

        if (!$program) {
            return $this->notFoundResponse('Program not found');
        }

        $statusMap = [
            'Open' => 'open',
            'Close' => 'closed',
        ];

        $enrollmentLimit = $payload['enrollment_limit'] ?? null;
        $capacityValue = $enrollmentLimit === 'unlimited'
            ? 9999
            : (int) ($payload['capacity'] ?? 1);

        $payloadStatus = $payload['status'] ?? null;
        $normalizedStatus = $statusMap[$payloadStatus] ?? ($payloadStatus ?: 'open');

        $sessionData = [
            'program_id' => $program->id,
            'title' => $payload['course'] ?? $payload['title'] ?? 'Session',
            'start_date' => $payload['date'] ?? now()->toDateString(),
            'end_date' => $payload['date'] ?? now()->toDateString(),
            'start_time' => $payload['start_time'] ? Carbon::parse($payload['start_time'])->format('H:i') : null,
            'end_time' => $payload['end_time'] ? Carbon::parse($payload['end_time'])->format('H:i') : null,
            'capacity' => $capacityValue,
            'trainer_id' => $payload['trainer_id'] ?? $adminRequest->requester_id,
            'trainer_name' => $payload['trainer'] ?? $payload['trainer_name'] ?? null,
            'trainer_photo_url' => $payload['trainer_photo_url'] ?? null,
            'location' => $payload['location'] ?? null,
            'status' => $normalizedStatus,
            'approval_status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'approval_note' => $adminRequest->admin_note,
        ];

        return DB::transaction(function () use ($request, $adminRequest, $sessionData) {
            if ($adminRequest->action === 'create') {
                // If this request was approved before, update the existing session instead of creating duplicates
                if ($adminRequest->target_id) {
                    $session = TrainingSession::find($adminRequest->target_id);
                    if ($session) {
                        $session->update($sessionData);
                    } else {
                        $session = TrainingSession::create($sessionData);
                        $adminRequest->target_id = $session->id;
                    }
                } else {
                    $session = TrainingSession::create($sessionData);
                    $adminRequest->target_id = $session->id;
                }
            } elseif ($adminRequest->action === 'update') {
                $session = TrainingSession::find($adminRequest->target_id);
                if (!$session) {
                    return $this->notFoundResponse('Session not found');
                }
                $session->update($sessionData);
            } else { // delete
                $session = TrainingSession::find($adminRequest->target_id);
                if (!$session) {
                    return $this->notFoundResponse('Session not found');
                }
                $session->delete();
            }

            $adminRequest->status = 'approved';
            $adminRequest->resolved_by = $request->user()->id;
            $adminRequest->resolved_at = now();
            $adminRequest->save();

            return $this->successResponse($adminRequest->fresh(), 'Session request approved');
        });
    }

    protected function approveTrainee(Request $request, AdminRequest $adminRequest)
    {
        $payload = $adminRequest->payload ?? [];
        $sessionId = $payload['session_id'] ?? null;
        $email = $payload['email'] ?? null;
        $fullName = $payload['full_name'] ?? null;

        if (!$sessionId) {
            return $this->validationErrorResponse(['session_id' => ['Session is required for trainee requests']]);
        }

        $session = TrainingSession::withCount([
            'enrollments as active_enrollments_count' => function ($q) {
                $q->where('status', '!=', 'cancelled');
            },
        ])->find($sessionId);

        if (!$session) {
            return $this->notFoundResponse('Session not found');
        }

        if ($session->active_enrollments_count >= $session->capacity) {
            return $this->validationErrorResponse(['capacity' => ['Session capacity is full']]);
        }

        if (!$email || !$fullName) {
            return $this->validationErrorResponse(['email' => ['Full name and email are required']]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $studentRoleId = Role::where('name', 'student')->value('id');
            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'password' => bcrypt(Str::random(12)),
                'role_id' => $studentRoleId,
            ]);
        }

        $enrollment = Enrollment::firstOrCreate(
            [
                'user_id' => $user->id,
                'session_id' => $session->id,
            ],
            [
                'status' => 'confirmed',
                'enrolled_at' => now(),
            ]
        );

        $adminRequest->target_id = $enrollment->id;
        $adminRequest->status = 'approved';
        $adminRequest->resolved_by = $request->user()->id;
        $adminRequest->resolved_at = now();
        $adminRequest->save();

        return $this->successResponse($adminRequest->fresh(), 'Trainee request approved');
    }
}
