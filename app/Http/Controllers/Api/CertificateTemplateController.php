<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\Program;
use App\Models\TrainingSession;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        // Validate layout coordinates are within image bounds
        $this->validateLayoutBounds(
            $data['layout_config'] ?? null,
            $data['background_image'] ?? null
        );

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

        // Validate layout coordinates are within image bounds
        // Use existing background_image if no new one is provided
        $backgroundImage = $data['background_image'] ?? $certificateTemplate->background_image;
        $this->validateLayoutBounds(
            $data['layout_config'] ?? null,
            $backgroundImage
        );

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
        // KAN-393: Get max background image size from config
        $maxBackgroundSize = config('certificates.max_file_sizes.background_image', 5120);

        $rules = [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'scope' => [$isCreate ? 'required' : 'sometimes', Rule::in(['global', 'program', 'session'])],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'session_id' => ['nullable', 'integer', 'exists:training_sessions,id'],
            'background_image' => [
                'nullable',
                'image',
                "max:{$maxBackgroundSize}",
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $imageInfo = @getimagesize($value->getRealPath());
                        if ($imageInfo) {
                            [$width, $height] = $imageInfo;
                            // Reject too small (quality loss)
                            if ($width < 800 || $height < 600) {
                                $fail("The {$attribute} must be at least 800x600 pixels for acceptable quality.");
                            }
                            // Reject too large (memory issues)
                            if ($width > 10000 || $height > 10000) {
                                $fail("The {$attribute} dimensions are too large (max 10000x10000 pixels).");
                            }
                        }
                    }
                },
            ],
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
            $imageService = app(ImageProcessingService::class);
            $originalContent = $file->getContent();
            $originalDimensions = $imageService->getImageDimensions($originalContent);

            // Auto-resize to standard dimensions (1920x1080)
            $resized = $imageService->resizeToStandard(
                $originalContent,
                $file->getMimeType()
            );

            $data['background_image'] = $resized['content'];
            $data['background_mime_type'] = $resized['mime_type']; // Always image/png after resize

            if (!empty($data['layout_config'])) {
                $data['layout_config'] = $this->scaleLayoutConfigForStandardCanvas(
                    $data['layout_config'],
                    $originalDimensions['width'],
                    $originalDimensions['height']
                );
            }
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

    private function scaleLayoutConfigForStandardCanvas(array $layoutConfig, int $sourceWidth, int $sourceHeight): array
    {
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return $layoutConfig;
        }

        $targetWidth = ImageProcessingService::STANDARD_WIDTH;
        $targetHeight = ImageProcessingService::STANDARD_HEIGHT;

        $scaleWidth = $targetWidth / $sourceWidth;
        $scaleHeight = $targetHeight / $sourceHeight;
        $scale = min($scaleWidth, $scaleHeight);

        $scaledWidth = $sourceWidth * $scale;
        $scaledHeight = $sourceHeight * $scale;

        $offsetX = (int) round(($targetWidth - $scaledWidth) / 2);
        $offsetY = (int) round(($targetHeight - $scaledHeight) / 2);

        $scaledConfig = $layoutConfig;
        foreach ($layoutConfig as $key => $config) {
            if ($key === 'canvas' || !is_array($config)) {
                continue;
            }

            $scaledConfig[$key] = $config;
            if (array_key_exists('x', $config)) {
                $scaledConfig[$key]['x'] = $this->scaleCoordinate($config['x'], $scale, $offsetX);
            }
            if (array_key_exists('y', $config)) {
                $scaledConfig[$key]['y'] = $this->scaleCoordinate($config['y'], $scale, $offsetY);
            }

            if ($key === 'qr') {
                if (array_key_exists('width', $config)) {
                    $scaledConfig[$key]['width'] = $this->scaleSize($config['width'], $scale);
                }
                if (array_key_exists('height', $config)) {
                    $scaledConfig[$key]['height'] = $this->scaleSize($config['height'], $scale);
                }
                if (array_key_exists('size', $config)) {
                    $scaledConfig[$key]['size'] = $this->scaleSize($config['size'], $scale);
                }
            }
        }

        $scaledConfig['canvas'] = [
            'width' => $targetWidth,
            'height' => $targetHeight,
        ];

        return $scaledConfig;
    }

    private function scaleCoordinate($value, float $scale, int $offset)
    {
        if (is_string($value) && str_contains($value, '%')) {
            return $value;
        }
        if (!is_numeric($value)) {
            return $value;
        }

        return (int) round(((float) $value * $scale) + $offset);
    }

    private function scaleSize($value, float $scale)
    {
        if (is_string($value) && str_contains($value, '%')) {
            return $value;
        }
        if (!is_numeric($value)) {
            return $value;
        }

        return (int) round((float) $value * $scale);
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

    /**
     * Validate that layout coordinates are within image bounds.
     *
     * @param array|null $layoutConfig Layout configuration with coordinates
     * @param string|null $backgroundImage Binary image content
     * @throws ValidationException if coordinates are out of bounds
     */
    private function validateLayoutBounds(?array $layoutConfig, ?string $backgroundImage): void
    {
        if (!$layoutConfig || !$backgroundImage) {
            return; // No validation needed if no layout or background
        }

        $imageService = app(ImageProcessingService::class);
        $dimensions = $imageService->getImageDimensions($backgroundImage);
        $width = $dimensions['width'];
        $height = $dimensions['height'];

        $errors = [];

        foreach ($layoutConfig as $field => $config) {
            if ($field === 'canvas') {
                continue; // Canvas is only for templates without background
            }

            if (!is_array($config)) {
                continue; // Skip non-array config values
            }

            // Extract coordinates (support both pixel and percentage)
            $x = $this->resolveCoordinate($config['x'] ?? 0, $width);
            $y = $this->resolveCoordinate($config['y'] ?? 0, $height);

            // Validate X coordinate
            if ($x < 0 || $x > $width) {
                $errors["layout_config.{$field}.x"] = "X coordinate ({$x}) must be within image width (0-{$width})";
            }

            // Validate Y coordinate
            if ($y < 0 || $y > $height) {
                $errors["layout_config.{$field}.y"] = "Y coordinate ({$y}) must be within image height (0-{$height})";
            }

            // Validate QR code bounds (has width/height)
            if ($field === 'qr') {
                $qrWidth = $this->resolveCoordinate($config['width'] ?? $config['size'] ?? 160, $width);
                $qrHeight = $this->resolveCoordinate($config['height'] ?? $config['size'] ?? 160, $height);

                if ($x + $qrWidth > $width) {
                    $errors["layout_config.{$field}.width"] = "QR code extends beyond image width";
                }
                if ($y + $qrHeight > $height) {
                    $errors["layout_config.{$field}.height"] = "QR code extends beyond image height";
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Resolve coordinate value (supports both pixel integers and percentage strings).
     *
     * @param mixed $value Coordinate value (int or "50%")
     * @param int $dimension Canvas dimension (width or height)
     * @return int Resolved pixel value
     */
    private function resolveCoordinate($value, int $dimension): int
    {
        // Handle percentage strings (e.g., "50%")
        if (is_string($value) && str_ends_with($value, '%')) {
            $percentage = (float) rtrim($value, '%');
            return (int) round($dimension * $percentage / 100);
        }

        // Handle pixel values (int or numeric string)
        return (int) $value;
    }
}
