<?php

namespace App\Services;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Support\Facades\Log;

class CertificateRenderer
{
    /**
     * Render certificate to PDF.
     *
     * @param Certificate $certificate
     * @return string Binary PDF data
     * @throws \Exception
     */
    public function render(Certificate $certificate): string
    {
        // Load all necessary relationships
        $certificate->loadMissing([
            'user:id,name',
            'course:id,title,description',
            'session:id,title,start_date,end_date',
            'issuer:id,name',
            'enrollment',
        ]);

        // Prepare template data
        $data = $this->prepareTemplateData($certificate);

        // Select template based on language
        $template = $certificate->language === 'en'
            ? 'certificates.templates.fixed-en'
            : 'certificates.templates.fixed-th';

        try {
            // Render PDF
            $pdf = Pdf::loadView($template, $data)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'sarabun',
                    'enable_php' => false,
                    'dpi' => 150,
                ]);

            return $pdf->output();
        } catch (\Exception $e) {
            Log::error('Certificate rendering failed', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception("Failed to render certificate: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Prepare template data from certificate.
     *
     * @param Certificate $certificate
     * @return array
     */
    protected function prepareTemplateData(Certificate $certificate): array
    {
        $session = $certificate->session;
        $course = $certificate->course;

        return [
            'certificate' => $certificate,
            'trainee_name' => $certificate->user->name ?? 'Unknown',
            'course_name' => $course->title ?? $course->name ?? 'Unknown Course',
            'course_description' => $certificate->description ?? $course->description ?? '',
            'session_name' => $session?->title ?? $session?->name,
            'start_date' => $session?->start_date ? $session->start_date->format('d/m/Y') : null,
            'end_date' => $session?->end_date ? $session->end_date->format('d/m/Y') : null,
            'total_hours' => $certificate->total_hours,
            'issue_date' => $certificate->issued_at->format('d/m/Y'),
            'certificate_code' => $certificate->certificate_code,
            'trainers' => $certificate->trainers(),
            'trainer_signatures' => $certificate->trainer_signatures ?? [],
            'authorized_signatory' => $certificate->authorized_signatory_name
                ?? config('certificates.default_signatory'),
            'authorized_signature' => $certificate->authorized_signature_url,
            'organization_name' => $certificate->organization_name
                ?? config('certificates.organization_name', 'KKU'),
            'organization_logo' => $this->getOrganizationLogo($certificate),
            'score' => $certificate->score,
            'skills' => $certificate->skills,
            'verification_url' => $certificate->verification_url,
            'qr_code' => $this->generateQrCode($certificate->verification_url),
            'language' => $certificate->language,
        ];
    }

    /**
     * Get organization logo path or URL.
     *
     * @param Certificate $certificate
     * @return string
     */
    protected function getOrganizationLogo(Certificate $certificate): string
    {
        // Use certificate-specific logo if available
        if ($certificate->organization_logo_url) {
            return $this->resolveAssetPath($certificate->organization_logo_url);
        }

        // Use default from config
        $defaultLogo = config('certificates.organization_logo_url', '/images/kku-logo.png');
        return $this->resolveAssetPath($defaultLogo);
    }

    /**
     * Resolve asset path for use in PDF.
     *
     * @param string $path
     * @return string
     */
    protected function resolveAssetPath(string $path): string
    {
        // If it's already an absolute path or URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }

        // Convert relative path to absolute file path
        $publicPath = public_path($path);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        // If file doesn't exist, return the URL (will let DOMPDF try to fetch it)
        return asset($path);
    }

    /**
     * Generate QR code as base64 data URI.
     *
     * @param string $url
     * @return string
     */
    protected function generateQrCode(string $url): string
    {
        try {
            $qrConfig = config('certificates.qr_code', [
                'size' => 200,
                'error_correction' => 'H',
                'margin' => 1,
            ]);

            $errorCorrection = match($qrConfig['error_correction']) {
                'L' => ErrorCorrectionLevel::Low,
                'M' => ErrorCorrectionLevel::Medium,
                'Q' => ErrorCorrectionLevel::Quartile,
                'H' => ErrorCorrectionLevel::High,
                default => ErrorCorrectionLevel::High,
            };

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($url)
                ->size($qrConfig['size'])
                ->margin($qrConfig['margin'])
                ->errorCorrectionLevel($errorCorrection)
                ->build();

            $qrCode = $result->getString();

            return 'data:image/png;base64,' . base64_encode($qrCode);
        } catch (\Exception $e) {
            Log::warning('QR code generation failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            // Return empty data URI as fallback
            return 'data:image/png;base64,';
        }
    }

    /**
     * Validate certificate text length and return warnings.
     *
     * @param Certificate $certificate
     * @return array
     */
    public function validateTextLength(Certificate $certificate): array
    {
        $warnings = [];
        $limits = config('certificates.text_limits', [
            'trainee_name' => 50,
            'course_name' => 100,
            'description' => 500,
            'skills' => 300,
        ]);

        $certificate->loadMissing('user', 'course');

        // Check trainee name
        $nameLength = mb_strlen($certificate->user?->name ?? '');
        if ($nameLength > $limits['trainee_name']) {
            $warnings[] = "Trainee name exceeds recommended length ({$limits['trainee_name']} characters). Current: {$nameLength} characters.";
        }

        // Check course name
        $courseNameLength = mb_strlen($certificate->course?->title ?? '');
        if ($courseNameLength > $limits['course_name']) {
            $warnings[] = "Course name exceeds recommended length ({$limits['course_name']} characters). Current: {$courseNameLength} characters.";
        }

        // Check description
        if ($certificate->description) {
            $descLength = mb_strlen($certificate->description);
            if ($descLength > $limits['description']) {
                $warnings[] = "Description exceeds recommended length ({$limits['description']} characters). Current: {$descLength} characters.";
            }
        }

        // Check skills
        if ($certificate->skills) {
            $skillsLength = mb_strlen($certificate->skills);
            if ($skillsLength > $limits['skills']) {
                $warnings[] = "Skills text exceeds recommended length ({$limits['skills']} characters). Current: {$skillsLength} characters.";
            }
        }

        return $warnings;
    }

    /**
     * Preview certificate with sample data (for testing templates).
     *
     * @param array $sampleData
     * @param string $language
     * @return string Binary PDF data
     */
    public function renderPreview(array $sampleData, string $language = 'th'): string
    {
        $template = $language === 'en'
            ? 'certificates.templates.fixed-en'
            : 'certificates.templates.fixed-th';

        // Merge with defaults
        $data = array_merge([
            'trainee_name' => 'Sample Trainee Name',
            'course_name' => 'Sample Course Name',
            'course_description' => 'This is a sample course description for preview purposes.',
            'session_name' => 'Sample Session',
            'start_date' => '01/01/2026',
            'end_date' => '31/01/2026',
            'total_hours' => 40,
            'issue_date' => date('d/m/Y'),
            'certificate_code' => 'CERT-PREVIEW-0000',
            'trainers' => collect([
                (object)['name' => 'Trainer Name 1'],
                (object)['name' => 'Trainer Name 2'],
            ]),
            'trainer_signatures' => [],
            'authorized_signatory' => 'Director Name',
            'authorized_signature' => null,
            'organization_name' => config('certificates.organization_name', 'KKU'),
            'organization_logo' => $this->resolveAssetPath(config('certificates.organization_logo_url', '/images/kku-logo.png')),
            'score' => null,
            'skills' => 'Sample skills and competencies gained from this training.',
            'verification_url' => url('/verify/CERT-PREVIEW-0000'),
            'qr_code' => $this->generateQrCode(url('/verify/CERT-PREVIEW-0000')),
            'language' => $language,
        ], $sampleData);

        $pdf = Pdf::loadView($template, $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sarabun',
                'enable_php' => false,
                'dpi' => 150,
            ]);

        return $pdf->output();
    }

    /**
     * Get estimated file size before rendering (for planning).
     *
     * @return array
     */
    public function getEstimatedFileSize(): array
    {
        return [
            'min_size_kb' => 50,
            'avg_size_kb' => 150,
            'max_size_kb' => 500,
            'max_allowed_kb' => config('certificates.max_file_sizes.certificate_file', 2048),
        ];
    }
}
