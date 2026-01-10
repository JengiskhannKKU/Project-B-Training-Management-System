<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificateRequest;
use App\Services\CertificateRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TrainerCertificateRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CertificateRequestService $requestService
    ) {}

    /**
     * List trainer's certificate requests
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CertificateRequest::class);

        $requests = $this->requestService->getRequests(
            scopedToTrainer: $request->user(),
            status: $request->query('status'),
            type: $request->query('type'),
            courseId: $request->query('course_id') ? (int) $request->query('course_id') : null,
            sessionId: $request->query('session_id') ? (int) $request->query('session_id') : null,
            search: $request->query('search'),
            perPage: $request->query('per_page', 15)
        );

        return $this->successResponse($requests, 'Certificate requests retrieved successfully.');
    }

    /**
     * View specific request (with authorization check)
     */
    public function show(CertificateRequest $certificateRequest): JsonResponse
    {
        $this->authorize('view', $certificateRequest);

        $certificateRequest->load([
            'enrollment.user',
            'enrollment.attendances',
            'session.course',
            'session.trainer',
            'course',
            'course.owner',
            'approver',
            'requester',
        ]);

        // Add validation data
        $validation = $this->requestService->validateRequest($certificateRequest);

        // Get certificate template info
        $templateInfo = null;
        if ($certificateRequest->type === 'session' && $certificateRequest->session) {
            $template = $certificateRequest->session->certificateTemplates()
                ->where('is_active', true)
                ->first();
            if ($template) {
                $templateInfo = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'scope' => $template->scope,
                ];
            }
        }

        if (!$templateInfo && $certificateRequest->course) {
            $template = $certificateRequest->course->activeCertificateTemplate()->first();
            if ($template) {
                $templateInfo = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'scope' => $template->scope,
                ];
            }
        }

        // Calculate attendance info if enrollment exists
        $attendanceInfo = null;
        if ($certificateRequest->enrollment) {
            $totalDays = 1; // Default for single-day sessions
            if ($certificateRequest->session) {
                $startDate = new \DateTime($certificateRequest->session->start_date);
                $endDate = new \DateTime($certificateRequest->session->end_date);
                $totalDays = max(1, $startDate->diff($endDate)->days + 1);
            }

            $attendanceCount = $certificateRequest->enrollment->attendances()
                ->whereIn('status', ['present', 'late'])
                ->count();

            $attendanceInfo = [
                'attendance_count' => $attendanceCount,
                'total_days' => $totalDays,
                'attendance_rate' => $totalDays > 0 ? round(($attendanceCount / $totalDays) * 100, 2) : 0,
            ];
        }

        return $this->successResponse([
            'request' => $certificateRequest,
            'validation' => $validation,
            'certificate_template' => $templateInfo,
            'attendance' => $attendanceInfo,
        ], 'Certificate request retrieved successfully.');
    }

    /**
     * Approve certificate request
     */
    public function approve(Request $request, CertificateRequest $certificateRequest): JsonResponse
    {
        $this->authorize('approve', $certificateRequest);

        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $certificate = $this->requestService->approve(
                request: $certificateRequest,
                approver: $request->user(),
                note: $request->input('note')
            );

            return $this->successResponse([
                'certificate_request' => $certificateRequest->fresh(),
                'certificate' => $certificate,
            ], 'Certificate request approved successfully.');

        } catch (\InvalidArgumentException $e) {
            return $this->validationErrorResponse([
                'request' => [$e->getMessage()],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Reject certificate request
     */
    public function reject(Request $request, CertificateRequest $certificateRequest): JsonResponse
    {
        $this->authorize('reject', $certificateRequest);

        $request->validate([
            'note' => 'required|string|min:10|max:1000',
        ]);

        try {
            $updatedRequest = $this->requestService->reject(
                request: $certificateRequest,
                rejector: $request->user(),
                note: $request->input('note')
            );

            return $this->successResponse([
                'certificate_request' => $updatedRequest,
            ], 'Certificate request rejected successfully.');

        } catch (\InvalidArgumentException $e) {
            return $this->validationErrorResponse([
                'note' => [$e->getMessage()],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
