<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminRequest;
use App\Models\Enrollment;
use App\Models\Course;
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

    public function getPendingCount()
    {
        $count = AdminRequest::where('status', 'pending')->count();

        return $this->successResponse(['count' => $count], 'Pending request count retrieved successfully');
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

        // If this is a course request with a linked course, mark the course as archived or inactive
        if ($adminRequest->target_type === 'course' && $adminRequest->target_id) {
            Course::where('id', $adminRequest->target_id)->update([
                'status' => 'archived', // Or inactive if schema supported it, but 'archived' is standard
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
            'course' => $this->approveCourse($request, $adminRequest),
            'session' => $this->approveSession($request, $adminRequest),
            'trainee' => $this->approveTrainee($request, $adminRequest),
            default => $this->validationErrorResponse(['target_type' => ['Unsupported request type']]),
        };
    }

    protected function approveCourse(Request $request, AdminRequest $adminRequest)
    {
        $payload = $adminRequest->payload ?? [];

        return DB::transaction(function () use ($request, $adminRequest, $payload) {
            $courseData = [
                'title' => $payload['title'] ?? $payload['name'] ?? 'Untitled Course',
                'description' => $payload['description'] ?? $payload['full_description'] ?? $payload['short_description'] ?? null,
                'category' => $payload['category'] ?? 'General',
                'level' => $payload['level'] ?? 'beginner',
                'learning_outcomes' => $payload['learning_outcomes'] ?? null,
                'target_audience' => $payload['target_audience'] ?? null,
                'prerequisites' => $payload['prerequisites'] ?? null,
                'additional_info' => $payload['additional_info'] ?? null,
                'thumbnail_path' => $payload['thumbnail_path'] ?? $payload['image_url'] ?? null,
                'status' => $payload['status'] ?? 'published',
                'min_participants' => $payload['min_participants'] ?? 1,
                'max_participants' => $payload['max_participants'] ?? 20,
                'owner_id' => $adminRequest->requester_id,
            ];

            $course = null;

            if ($adminRequest->action === 'create') {
                if ($adminRequest->target_id) {
                    $course = Course::find($adminRequest->target_id);
                }

                if (!$course) {
                    $course = Course::create($courseData);
                    $adminRequest->target_id = $course->id;
                } else {
                    $course->update($courseData);
                }
            } else {
                $course = Course::find($adminRequest->target_id);
                if (!$course) {
                    return $this->notFoundResponse('Course not found');
                }
                $course->update($courseData);
            }

            $adminRequest->status = 'approved';
            $adminRequest->resolved_by = $request->user()->id;
            $adminRequest->resolved_at = now();
            $adminRequest->save();

            return $this->successResponse($adminRequest->fresh(), 'Course request approved');
        });
    }

    protected function approveSession(Request $request, AdminRequest $adminRequest)
    {
        $payload = $adminRequest->payload ?? [];

        $courseId = $payload['course_id'] ?? null;
        if (!$courseId) {
            return $this->validationErrorResponse(['course_id' => ['Course is required for sessions']]);
        }

        // Resolve course. Sometimes the payload may carry the course request id instead of the course id.
        $course = Course::find($courseId);
        if (!$course && $courseId) {
            $maybeCourseRequest = AdminRequest::where('id', $courseId)
                ->where('target_type', 'course')
                ->first();
            if ($maybeCourseRequest && $maybeCourseRequest->target_id) {
                $course = Course::find($maybeCourseRequest->target_id);
                if (!$course) {
                    // Create course if not exists (fallback logic, though approveCourse should handle this)
                    // ... simpler to just fail if parent course not approved yet
                    return $this->validationErrorResponse(['course_id' => ['Parent course not found or not approved']]);
                }
                // Update payload to the real course id for future attempts
                $payload['course_id'] = $course->id;
                $adminRequest->payload = $payload;
                $adminRequest->save();
                $courseId = $course->id;
            }
        }

        if (!$course) {
            return $this->notFoundResponse('Course not found');
        }

        $statusMap = [
            'Open' => 'open',
            'Close' => 'closed',
        ];

        $enrollmentLimit = $payload['enrollment_limit'] ?? null;
        $capacityValue = $enrollmentLimit === 'unlimited'
            ? 9999
            : (int) ($payload['capacity'] ?? $course->max_participants ?? 20);

        $payloadStatus = $payload['status'] ?? null;
        $normalizedStatus = $statusMap[$payloadStatus] ?? ($payloadStatus ?: 'open');

        $sessionData = [
            'course_id' => $course->id,
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
            'online_link' => $payload['online_link'] ?? null,
            'registration_start' => $payload['registration_start'] ?? null,
            'registration_end' => $payload['registration_end'] ?? null,
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
            $traineeRoleId = Role::where('name', 'trainee')->value('id');
            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'password' => bcrypt(Str::random(12)),
                'role_id' => $traineeRoleId,
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
