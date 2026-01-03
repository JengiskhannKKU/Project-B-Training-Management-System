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
        if (!$template) {
            throw new RuntimeException('No active certificate template found.');
        }

        $renderer = new CertificateRenderer();
        $rendered = $renderer->render($certificate, $template);
        $binary = $rendered['binary'] ?? null;
        $mimeType = $rendered['mime_type'] ?? null;

        if (!$binary || !$mimeType) {
            throw new RuntimeException('Certificate rendering failed.');
        }

        $certificate->forceFill([
            'file_data' => $binary,
            'file_mime_type' => $mimeType,
            'file_size' => strlen($binary),
            'generated_at' => now(),
            'template_id' => $template->id,
        ])->save();

        return $certificate->fresh();
    }

    private function resolveTemplate(Certificate $certificate): ?CertificateTemplate
    {
        if ($certificate->template_id) {
            return CertificateTemplate::find($certificate->template_id);
        }

        $session = $certificate->session ?? $certificate->enrollment?->session;
        if ($session) {
            $sessionTemplate = $session->activeCertificateTemplate()->first();
            if ($sessionTemplate) {
                return $sessionTemplate;
            }
        }

        $program = $certificate->program ?? $session?->program;
        if ($program) {
            $programTemplate = $program->activeCertificateTemplate()->first();
            if ($programTemplate) {
                return $programTemplate;
            }
        }

        $globalTemplate = CertificateTemplate::where('scope', 'global')
            ->where('is_active', true)
            ->latest()
            ->first();

        if ($globalTemplate) {
            return $globalTemplate;
        }

        return $this->createDefaultGlobalTemplate();
    }

    private function createDefaultGlobalTemplate(): CertificateTemplate
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

        return CertificateTemplate::create([
            'name' => 'Default Global Template',
            'scope' => 'global',
            'layout_config' => $layout,
            'font_size' => 28,
            'text_color' => '#1f2937',
            'is_active' => true,
        ]);
    }

    private function hasFileData(Certificate $certificate): bool
    {
        $data = $certificate->file_data;

        return $data !== null && $data !== '';
    }
}
