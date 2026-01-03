<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\Program;
use App\Models\TrainingSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CertificateTemplateController extends Controller
{
    public function index()
    {
        $user = request()->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $templates = CertificateTemplate::with([
            'program:id,name,created_by',
            'session:id,title,program_id,trainer_id',
        ])
            ->latest();

        if (!$user->isRole('admin')) {
            $templates->where(function ($query) use ($user) {
                $query->where(function ($builder) use ($user) {
                    $builder->where('scope', 'program')
                        ->whereHas('program', function ($programQuery) use ($user) {
                            $programQuery->where('created_by', $user->id);
                        });
                })->orWhere(function ($builder) use ($user) {
                    $builder->where('scope', 'session')
                        ->whereHas('session', function ($sessionQuery) use ($user) {
                            $sessionQuery->where('trainer_id', $user->id);
                        });
                });
            });
        }

        $templates = $templates
            ->get()
            ->map(function (CertificateTemplate $template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'scope' => $template->scope,
                    'program' => $template->program,
                    'session' => $template->session,
                    'is_active' => $template->is_active,
                    'has_background' => (bool) $template->background_image,
                    'updated_at' => $template->updated_at,
                    'created_at' => $template->created_at,
                ];
            });

        return $this->successResponse($templates, 'Certificate templates retrieved successfully.');
    }

    public function show(CertificateTemplate $certificateTemplate)
    {
        $user = request()->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $certificateTemplate->load(['program:id,name,created_by', 'session:id,title,program_id,trainer_id']);

        if ($user->isRole('trainer')) {
            $denial = $this->denyIfTrainerCannotAccess($user->id, $certificateTemplate);
            if ($denial) {
                return $denial;
            }
        }

        $data = $certificateTemplate->makeHidden('background_image')->toArray();
        $data['background_image_url'] = $this->buildBackgroundDataUrl($certificateTemplate);

        return $this->successResponse($data, 'Certificate template retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, true);

        if ($request->user()?->isRole('trainer')) {
            $denial = $this->denyIfTrainerCannotManage(
                $request->user()->id,
                $validated,
                null
            );
            if ($denial) {
                return $denial;
            }
        }

        $data = $this->buildTemplateData($request, $validated);

        $template = CertificateTemplate::create($data);

        return $this->createdResponse(
            $template->load(['program:id,name', 'session:id,title,program_id'])
                ->makeHidden('background_image'),
            'Certificate template created successfully.'
        );
    }

    public function update(Request $request, CertificateTemplate $certificateTemplate)
    {
        $validated = $this->validatePayload($request, false);

        if ($request->user()?->isRole('trainer')) {
            $denial = $this->denyIfTrainerCannotManage(
                $request->user()->id,
                $validated,
                $certificateTemplate
            );
            if ($denial) {
                return $denial;
            }
        }

        $data = $this->buildTemplateData($request, $validated, $certificateTemplate);

        $certificateTemplate->update($data);

        return $this->successResponse(
            $certificateTemplate->fresh()
                ->load(['program:id,name', 'session:id,title,program_id'])
                ->makeHidden('background_image'),
            'Certificate template updated successfully.'
        );
    }

    public function destroy(CertificateTemplate $certificateTemplate)
    {
        $user = request()->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if ($user->isRole('trainer')) {
            $certificateTemplate->load(['program:id,created_by', 'session:id,trainer_id']);
            $denial = $this->denyIfTrainerCannotAccess($user->id, $certificateTemplate);
            if ($denial) {
                return $denial;
            }
        }

        $certificateTemplate->delete();

        return $this->successResponse(null, 'Certificate template deleted successfully.');
    }

    private function validatePayload(Request $request, bool $isCreate): array
    {
        $rules = [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'scope' => [$isCreate ? 'required' : 'sometimes', Rule::in(['global', 'program', 'session'])],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'session_id' => ['nullable', 'integer', 'exists:training_sessions,id'],
            'background_image' => ['nullable', 'image', 'max:4096'],
            'layout_config' => ['nullable'],
            'font_family' => ['nullable', 'string', 'max:255'],
            'font_size' => ['nullable', 'integer', 'min:1', 'max:200'],
            'text_color' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable'],
        ];

        $validated = $request->validate($rules);

        $scope = $validated['scope'] ?? $request->input('scope');
        if ($scope === 'program' && empty($validated['program_id'])) {
            $request->validate([
                'program_id' => ['required', 'integer', 'exists:programs,id'],
            ]);
        }

        if ($scope === 'session' && empty($validated['session_id'])) {
            $request->validate([
                'session_id' => ['required', 'integer', 'exists:training_sessions,id'],
            ]);
        }

        return $validated;
    }

    private function denyIfTrainerCannotAccess(int $trainerId, CertificateTemplate $template): ?JsonResponse
    {
        if ($template->scope === 'global') {
            return $this->forbiddenResponse('Only admin can access global certificate templates.');
        }

        if ($template->scope === 'program') {
            if (!$template->program || $template->program->created_by !== $trainerId) {
                return $this->forbiddenResponse('Only the program owner can access this certificate template.');
            }
        }

        if ($template->scope === 'session') {
            if (!$template->session || $template->session->trainer_id !== $trainerId) {
                return $this->forbiddenResponse('Only the session trainer can access this certificate template.');
            }
        }

        return null;
    }

    private function denyIfTrainerCannotManage(int $trainerId, array $payload, ?CertificateTemplate $template): ?JsonResponse
    {
        $scope = $payload['scope'] ?? $template?->scope ?? 'global';

        if ($scope === 'global') {
            return $this->forbiddenResponse('Only admin can manage global certificate templates.');
        }

        $programId = $payload['program_id'] ?? $template?->program_id;
        $sessionId = $payload['session_id'] ?? $template?->session_id;

        if ($scope === 'program') {
            if (!$programId || !$this->trainerOwnsProgram($trainerId, $programId)) {
                return $this->forbiddenResponse('Only the program owner can manage this certificate template.');
            }
        }

        if ($scope === 'session') {
            if (!$sessionId || !$this->trainerOwnsSession($trainerId, $sessionId)) {
                return $this->forbiddenResponse('Only the session trainer can manage this certificate template.');
            }
        }

        return null;
    }

    private function trainerOwnsProgram(int $trainerId, int $programId): bool
    {
        return Program::where('id', $programId)
            ->where('created_by', $trainerId)
            ->exists();
    }

    private function trainerOwnsSession(int $trainerId, int $sessionId): bool
    {
        return TrainingSession::where('id', $sessionId)
            ->where('trainer_id', $trainerId)
            ->exists();
    }

    private function buildTemplateData(Request $request, array $validated, ?CertificateTemplate $template = null): array
    {
        $data = $validated;

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        } elseif (!$template) {
            $data['is_active'] = true;
        }

        if ($request->has('layout_config')) {
            $data['layout_config'] = $this->normalizeLayoutConfig(
                $request->input('layout_config')
            );
        } elseif (!$template && !array_key_exists('layout_config', $data)) {
            $data['layout_config'] = null;
        }

        if ($request->hasFile('background_image')) {
            $file = $request->file('background_image');
            $data['background_image'] = $file->getContent();
            $data['background_mime_type'] = $file->getMimeType();
        }

        $scope = $data['scope'] ?? $template?->scope;
        if ($scope === 'global') {
            $data['program_id'] = null;
            $data['session_id'] = null;
        } elseif ($scope === 'program') {
            $data['session_id'] = null;
        } elseif ($scope === 'session') {
            $data['program_id'] = $data['program_id'] ?? $template?->program_id;
        }

        return $data;
    }

    private function normalizeLayoutConfig($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            return $decoded;
        }

        return null;
    }

    private function buildBackgroundDataUrl(CertificateTemplate $template): ?string
    {
        if (!$template->background_image || !$template->background_mime_type) {
            return null;
        }

        return sprintf(
            'data:%s;base64,%s',
            $template->background_mime_type,
            base64_encode($template->background_image)
        );
    }
}
