<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use RuntimeException;

class CertificateFileService
{
    public function generateAndStoreFile(Certificate $certificate): Certificate
    {
        if ($this->hasFileData($certificate)) {
            return $certificate;
        }

        return $this->forceGenerateAndStoreFile($certificate);
    }

    public function forceGenerateAndStoreFile(Certificate $certificate): Certificate
    {
        $certificate->loadMissing(['session.program', 'program', 'enrollment.session.program']);

        $template = $this->resolveTemplate($certificate);

        $renderer = new CertificateRenderer();
        $rendered = $renderer->render($certificate, $template);
        $binary = $rendered['binary'] ?? null;
        $mimeType = $rendered['mime_type'] ?? null;

        if (!$binary || !$mimeType) {
            throw new RuntimeException('Certificate rendering failed.');
        }

        $fileSize = strlen($binary);

        // KAN-393: Validate file size against configured limit
        $this->validateFileSize($fileSize, $certificate);

        // template_id can be null if using fallback template (KAN-391)
        $certificate->forceFill([
            'file_data' => $binary,
            'file_mime_type' => $mimeType,
            'file_size' => $fileSize,
            'generated_at' => now(),
            'template_id' => $template->id ?? null,
        ])->save();

        return $certificate->fresh();
    }

    /**
     * Resolve template for certificate generation.
     *
     * KAN-390: Template selection order:
     * 1. Session-level template (if certificate is linked to a session)
     * 2. Program-level template
     * 3. Global default template
     * 4. Fallback to hardcoded basic template (KAN-391)
     *
     * @param Certificate $certificate
     * @return CertificateTemplate
     */
    private function resolveTemplate(Certificate $certificate): CertificateTemplate
    {
        // If certificate already has a template assigned, use it
        if ($certificate->template_id) {
            $template = CertificateTemplate::find($certificate->template_id);
            if ($template) {
                return $template;
            }
        }

        // KAN-390: Priority 1 - Session-level template
        $session = $certificate->session ?? $certificate->enrollment?->session;
        if ($session) {
            $sessionTemplate = $session->activeCertificateTemplate()->first();
            if ($sessionTemplate) {
                return $sessionTemplate;
            }
        }

        // KAN-390: Priority 2 - Program-level template
        $program = $certificate->program ?? $session?->program;
        if ($program) {
            $programTemplate = $program->activeCertificateTemplate()->first();
            if ($programTemplate) {
                return $programTemplate;
            }
        }

        // KAN-390: Priority 3 - Global default template
        $globalTemplate = CertificateTemplate::where('scope', 'global')
            ->where('is_active', true)
            ->latest()
            ->first();

        if ($globalTemplate) {
            return $globalTemplate;
        }

        // KAN-391: Priority 4 - Hardcoded fallback template (no DB persistence)
        return $this->createFallbackTemplate();
    }

    /**
     * Create a hardcoded fallback template (KAN-391).
     * This template is NOT persisted to the database.
     * It's a basic in-memory template used when no other template is available.
     *
     * @return CertificateTemplate
     */
    private function createFallbackTemplate(): CertificateTemplate
    {
        $layout = [
            'canvas' => [
                'width' => 1600,
                'height' => 1200,
            ],
            'name' => [
                'x' => 192,
                'y' => 384,
            ],
            'program' => [
                'x' => 192,
                'y' => 504,
            ],
            'session' => [
                'x' => 192,
                'y' => 624,
            ],
            'issued_at' => [
                'x' => 192,
                'y' => 744,
            ],
            'certificate_code' => [
                'x' => 192,
                'y' => 864,
            ],
            'qr' => [
                'x' => 1152,
                'y' => 696,
                'width' => 160,
                'height' => 160,
                'size' => 160,
            ],
        ];

        // Create an in-memory template without saving to database
        $template = new CertificateTemplate();
        $template->name = 'Hardcoded Fallback Template';
        $template->scope = 'global';
        $template->layout_config = $layout;
        $template->font_size = 28;
        $template->text_color = '#1f2937';
        $template->is_active = true;

        return $template;
    }

    private function hasFileData(Certificate $certificate): bool
    {
        $data = $certificate->file_data;

        return $data !== null && $data !== '';
    }

    /**
     * Validate certificate file size against configured limits (KAN-393).
     *
     * @param int $fileSizeBytes File size in bytes
     * @param Certificate $certificate The certificate being generated
     * @throws RuntimeException if file size exceeds hard limit
     */
    private function validateFileSize(int $fileSizeBytes, Certificate $certificate): void
    {
        $fileSizeKB = round($fileSizeBytes / 1024, 2);
        $maxSizeKB = config('certificates.max_file_sizes.certificate_file', 2048);
        $logLargeFiles = config('certificates.monitoring.log_large_files', true);

        // Log warning if file is large (>50% of limit)
        $warningThresholdKB = $maxSizeKB * 0.5;
        if ($logLargeFiles && $fileSizeKB > $warningThresholdKB) {
            \Log::warning('Large certificate file generated', [
                'certificate_id' => $certificate->id,
                'certificate_code' => $certificate->certificate_code,
                'file_size_kb' => $fileSizeKB,
                'max_size_kb' => $maxSizeKB,
                'template_id' => $certificate->template_id,
            ]);
        }

        // Throw exception if file exceeds maximum size
        if ($fileSizeKB > $maxSizeKB) {
            throw new RuntimeException(
                "Certificate file size ({$fileSizeKB} KB) exceeds maximum allowed size ({$maxSizeKB} KB). " .
                "Consider optimizing the template or reducing background image size."
            );
        }
    }
}
