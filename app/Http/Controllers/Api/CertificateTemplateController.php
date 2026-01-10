<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CertificateTemplateController extends Controller
{
    /**
     * Display a listing of certificate templates for the trainer.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = CertificateTemplate::query();

        if ($user->isRole('admin')) {
            $query->with([
                'course:id,title,owner_id',
                'session:id,title,course_id,trainer_id'
            ]);
        } else {
            // Trainer sees:
            // 1. Templates they created (if we had a created_by field, but currently implied by ownership of course/session)
            // 2. Templates for their courses/sessions
            // 3. Global templates are usually managed by admin, but let's assume trainers manage their own context.
            
            // Simplified: Trainers see templates scoped to their resources
            $builder = $query->where(function ($q) use ($user) {
                // Session scope: session owned by trainer
                $q->where('scope', 'session')
                  ->whereHas('session', function ($sessionQuery) use ($user) {
                      $sessionQuery->where('trainer_id', $user->id);
                  });
            })->orWhere(function ($q) use ($user) {
                // Course scope: course owned by trainer
                $q->where('scope', 'course')
                  ->whereHas('course', function ($courseQuery) use ($user) {
                      $courseQuery->where('owner_id', $user->id);
                  });
            });
            
            $query->with(['course:id,title,owner_id', 'session:id,title,course_id,trainer_id']);
        }

        return $this->successResponse($query->latest()->get(), 'Templates retrieved successfully.');
    }

    /**
     * Store a newly created certificate template.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $isTrainer = $user->isRole('trainer');

        $validated = $this->validateTemplate($request, $isTrainer);

        // Additional authorization for scoped templates
        if ($isTrainer) {
            $this->authorizeTemplateScope($user->id, $validated['scope'], $validated);
        }

        // Process background image if present
        $backgroundImage = null;
        $backgroundMime = null;

        if ($request->hasFile('background_image')) {
            $file = $request->file('background_image');
            $backgroundImage = file_get_contents($file->getRealPath());
            $backgroundMime = $file->getMimeType();
        }

        $template = CertificateTemplate::create([
            'name' => $validated['name'],
            'scope' => $validated['scope'],
            'course_id' => $validated['course_id'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
            'background_image' => $backgroundImage,
            'background_mime_type' => $backgroundMime,
            'layout_config' => $validated['layout_config'] ?? null, // JSON will be cast automatically
            'font_family' => $validated['font_family'] ?? 'Prompt-Regular.ttf', // Default font
            'font_size' => $validated['font_size'] ?? 16,
            'text_color' => $validated['text_color'] ?? '#000000',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->createdResponse($template->load(['course:id,title', 'session:id,title,course_id']), 'Template created successfully.');
    }

    /**
     * Display the specified certificate template.
     */
    public function show(string $id): JsonResponse
    {
        $template = CertificateTemplate::findOrFail($id);
        $user = request()->user();

        // Check authorization
        if ($user->isRole('trainer')) {
            $this->authorizeTemplateAccess($user->id, $template);
        }

        $template->load(['course:id,title', 'session:id,title,course_id']);

        // Inject background_url if image exists
        $data = $template->toArray();
        if ($template->background_image) {
            $data['background_url'] = route('certificate-templates.background', $template->id);
        }

        return $this->successResponse($data, 'Template retrieved successfully.');
    }

    /**
     * Update the specified certificate template.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $template = CertificateTemplate::findOrFail($id);
        $user = $request->user();
        $isTrainer = $user->isRole('trainer');

        if ($isTrainer) {
            $this->authorizeTemplateAccess($user->id, $template);
        }

        $validated = $this->validateTemplate($request, $isTrainer, $template->id);

        // Check scope authorization if changing scope or targets
        if ($isTrainer) {
            // Merge existing values if partial update, to check consistency
            $checkScope = $validated['scope'] ?? $template->scope;
            $checkData = array_merge($template->toArray(), $validated);
            $this->authorizeTemplateScope($user->id, $checkScope, $checkData);
        }

        $updateData = collect($validated)->except(['background_image'])->toArray();

        if ($request->hasFile('background_image')) {
            $file = $request->file('background_image');
            $updateData['background_image'] = file_get_contents($file->getRealPath());
            $updateData['background_mime_type'] = $file->getMimeType();
        }

        $template->update($updateData);

        return $this->successResponse(
            $template->load(['course:id,title', 'session:id,title,course_id']), 
            'Template updated successfully.'
        );
    }

    /**
     * Remove the specified certificate template.
     */
    public function destroy(string $id): JsonResponse
    {
        $template = CertificateTemplate::findOrFail($id);
        $user = request()->user();

        if ($user->isRole('trainer')) {
            $this->authorizeTemplateAccess($user->id, $template);
        }

        $template->delete();

        return $this->successResponse(null, 'Template deleted successfully.');
    }

    // Helper methods

    private function validateTemplate(Request $request, bool $isTrainer, ?int $id = null): array
    {
        $isCreate = $request->isMethod('post');
        
        $rules = [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'scope' => [$isCreate ? 'required' : 'sometimes', Rule::in(['global', 'course', 'session'])],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'session_id' => ['nullable', 'integer', 'exists:training_sessions,id'],
            'background_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'], // Max 5MB
            'layout_config' => ['nullable', 'array'], // JSON structure validation could be stricter
            'font_family' => ['nullable', 'string'],
            'font_size' => ['nullable', 'integer', 'min:8', 'max:72'],
            'text_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'is_active' => ['boolean'],
        ];

        if ($isTrainer) {
            // Trainers cannot create global templates
            $rules['scope'] = [$isCreate ? 'required' : 'sometimes', Rule::in(['course', 'session'])];
        }

        $validated = $request->validate($rules);

        // Additional integrity checks
        $scope = $validated['scope'] ?? ($id ? CertificateTemplate::find($id)->scope : null);
        
        if ($scope === 'global') {
            $validated['course_id'] = null;
            $validated['session_id'] = null;
        } elseif ($scope === 'course') {
            $validated['session_id'] = null;
            if ($isCreate && empty($validated['course_id'])) {
                // If creating, must provide course_id
                $request->validate(['course_id' => 'required']);
            }
        } elseif ($scope === 'session') {
            $validated['course_id'] = null;
            if ($isCreate && empty($validated['session_id'])) {
                $request->validate(['session_id' => 'required']);
            }
        }

        return $validated;
    }

    private function authorizeTemplateAccess(int $trainerId, CertificateTemplate $template): void
    {
        if ($template->scope === 'global') {
            $this->forbiddenResponse('Trainers cannot modify global templates.'); // Should throw
            abort(403, 'Trainers cannot modify global templates.');
        }

        if ($template->scope === 'course') {
            if (!$template->course || $template->course->owner_id !== $trainerId) {
                abort(403, 'Only the course owner can access this certificate template.');
            }
        }

        if ($template->scope === 'session') {
            if (!$template->session || $template->session->trainer_id !== $trainerId) {
                abort(403, 'Only the session trainer can access this certificate template.');
            }
        }
    }

    private function authorizeTemplateScope(int $trainerId, string $scope, array $payload): void
    {
        if ($scope === 'global') {
            abort(403, 'Trainers cannot manage global templates.');
        }

        $courseId = $payload['course_id'] ?? null;
        $sessionId = $payload['session_id'] ?? null;

        if ($scope === 'course') {
            if (!$courseId || !$this->trainerOwnsCourse($trainerId, $courseId)) {
                abort(403, 'Only the course owner can manage this certificate template.');
            }
        }

        if ($scope === 'session') {
            if (!$sessionId || !$this->trainerOwnsSession($trainerId, $sessionId)) {
                abort(403, 'Only the session trainer can manage this certificate template.');
            }
        }
    }

    private function trainerOwnsCourse(int $trainerId, int $courseId): bool
    {
        return Course::where('id', $courseId)
            ->where('owner_id', $trainerId)
            ->exists();
    }

    private function trainerOwnsSession(int $trainerId, int $sessionId): bool
    {
        return TrainingSession::where('id', $sessionId)
            ->where('trainer_id', $trainerId)
            ->exists();
    }
}