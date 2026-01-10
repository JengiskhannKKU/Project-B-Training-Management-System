<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificateRequest;
use App\Services\CertificateRequestService;
use App\Services\CertificateGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCertificateRequestController extends Controller
{
    protected $requestService;

    public function __construct(CertificateRequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * Get all certificate requests (Admin view)
     */
    public function index(Request $request): JsonResponse
    {
        // Admin sees all requests
        $requests = $this->requestService->getRequests(
            scopedToTrainer: null, // No trainer scope for admin
            status: $request->query('status'),
            type: $request->query('type'),
            courseId: $request->query('course_id') ? (int) $request->query('course_id') : null,
            sessionId: $request->query('session_id') ? (int) $request->query('session_id') : null,
            trainerId: $request->query('trainer_id') ? (int) $request->query('trainer_id') : null,
            search: $request->query('search'),
            perPage: $request->query('per_page') ? (int) $request->query('per_page') : 15
        );

        return $this->successResponse($requests);
    }

    /**
     * Get specific certificate request details
     */
    public function show(CertificateRequest $certificateRequest): JsonResponse
    {
        $certificateRequest->load([
            'enrollment.user',
            'session.course',
            'session.trainer',
            'course',
            'course.owner',
            'approver',
            'requester',
        ]);

        // Check if template is available for this request context
        $templateInfo = null;
        if ($certificateRequest->type === 'session' && $certificateRequest->session) {
            $template = $certificateRequest->session->activeCertificateTemplate;
            if ($template) {
                $templateInfo = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'scope' => 'session'
                ];
            }
        }

        if (!$templateInfo && $certificateRequest->course) {
            $template = $certificateRequest->course->activeCertificateTemplate()->first();
            if ($template) {
                $templateInfo = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'scope' => 'course'
                ];
            }
        }

        if (!$templateInfo) {
            $globalTemplate = \App\Models\CertificateTemplate::where('scope', 'global')
                ->where('is_active', true)
                ->first();
            if ($globalTemplate) {
                $templateInfo = [
                    'id' => $globalTemplate->id,
                    'name' => $globalTemplate->name,
                    'scope' => 'global'
                ];
            }
        }

        return $this->successResponse([
            'request' => $certificateRequest,
            'template' => $templateInfo
        ]);
    }

    /**
     * Approve certificate request
     */
    public function approve(Request $request, CertificateRequest $certificateRequest): JsonResponse
    {
        $admin = $request->user();

        if ($certificateRequest->status !== 'pending') {
            return $this->validationErrorResponse(['status' => 'Request is not pending']);
        }

        try {
            $certificate = $this->requestService->approve(
                $certificateRequest,
                $admin,
                $request->input('note')
            );

            return $this->successResponse([
                'request' => $certificateRequest->fresh(),
                'certificate' => $certificate
            ], 'Certificate request approved and certificate generated.');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Reject certificate request
     */
    public function reject(Request $request, CertificateRequest $certificateRequest): JsonResponse
    {
        $admin = $request->user();

        $validated = $request->validate([
            'note' => ['required', 'string', 'min:5']
        ]);

        if ($certificateRequest->status !== 'pending') {
            return $this->validationErrorResponse(['status' => 'Request is not pending']);
        }

        try {
            $updatedRequest = $this->requestService->reject(
                $certificateRequest,
                $admin,
                $validated['note']
            );

            return $this->successResponse($updatedRequest, 'Certificate request rejected.');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}