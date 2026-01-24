<?php

namespace App\Services;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Support\Facades\Log;
use TCPDF;

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
        Log::debug('Starting certificate rendering', ['certificate_id' => $certificate->id]);
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
        Log::debug('Template data prepared', ['data' => array_keys($data)]);

        try {
            // Use TCPDF for Thai certificates (better Unicode support)
            if ($certificate->language === 'th') {
                Log::debug('Using TCPDF for Thai certificate');
                return $this->renderWithTcpdf($data);
            }

            // Use DomPDF for English certificates
            Log::debug('Using DomPDF for English certificate');
            $template = 'certificates.templates.fixed-en';
            $pdf = Pdf::loadView($template, $data)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                    'enable_php' => false,
                    'dpi' => 150,
                ]);

            Log::debug('Calling $pdf->output()');
            $output = $pdf->output();
            Log::debug('PDF rendered successfully', ['size' => strlen($output)]);
            return $output;
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
        // Check if Endroid\QrCode package is installed and classes exist
        if (
            !class_exists('\Endroid\QrCode\Builder\Builder') ||
            !class_exists('\Endroid\QrCode\Writer\PngWriter')
        ) {
            Log::warning('Endroid\QrCode package or classes not found. Skipping QR code generation.');
            return 'data:image/png;base64,';
        }

        try {
            $qrConfig = config('certificates.qr_code', [
                'size' => 200,
                'error_correction' => 'H',
                'margin' => 1,
            ]);

            // Robust check for ErrorCorrectionLevel class/enum
            $ecClass = '\Endroid\QrCode\ErrorCorrectionLevel';
            if (!class_exists($ecClass) && !enum_exists($ecClass)) {
                // Try alternate namespace/name for different versions if needed
                // For now, if it's missing, we'll just skip the specific level setting
                Log::warning('ErrorCorrectionLevel class/enum not found.');
                $errorCorrection = null;
            } else {
                $errorCorrection = match ($qrConfig['error_correction'] ?? 'H') {
                    'L' => $ecClass::Low,
                    'M' => $ecClass::Medium,
                    'Q' => $ecClass::Quartile,
                    'H' => $ecClass::High,
                    default => $ecClass::High,
                };
            }

            $builder = Builder::create()
                ->writer(new PngWriter())
                ->data($url)
                ->size($qrConfig['size'])
                ->margin($qrConfig['margin']);

            if ($errorCorrection) {
                $builder->errorCorrectionLevel($errorCorrection);
            }

            $result = $builder->build();
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
                (object) ['name' => 'Trainer Name 1'],
                (object) ['name' => 'Trainer Name 2'],
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

        $orientation = $language === 'en' ? 'landscape' : 'portrait';

        $pdf = Pdf::loadView($template, $data)
            ->setPaper('a4', $orientation)
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'enable_php' => false,
                'dpi' => 150,
            ]);

        return $pdf->output();
    }

    /**
     * Render Thai certificate using TCPDF with proper Unicode support.
     *
     * @param array $data
     * @return string
     */
    protected function renderWithTcpdf(array $data): string
    {
        // Create TCPDF object in portrait orientation
        $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document info
        $pdf->SetCreator('KKU Training Management System');
        $pdf->SetAuthor('KKU');
        $pdf->SetTitle('ใบประกาศนียบัตร - ' . ($data['trainee_name'] ?? ''));
        $pdf->SetSubject('Certificate of Completion');

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);

        // Remove header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set auto page break
        $pdf->SetAutoPageBreak(true, 15);

        // Add page
        $pdf->AddPage();

        // Set font - use freeserif which has Thai support in TCPDF
        // TCPDF includes several Unicode fonts: freeserif, freesans, dejavusans
        $pdf->SetFont('freeserif', '', 14, '', true);

        // Build HTML content for Thai certificate
        $html = $this->buildThaiCertificateHtml($data);

        // Output HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Return PDF as string
        return $pdf->Output('', 'S');
    }

    /**
     * Build HTML content for Thai certificate TCPDF rendering.
     *
     * @param array $data
     * @return string
     */
    protected function buildThaiCertificateHtml(array $data): string
    {
        // Get QR code (already base64 encoded from generateQrCode)
        $qrCodeData = $data['qr_code'] ?? '';

        // Build signature HTML
        $signaturesHtml = '';
        $displayTrainers = collect($data['trainers'])->take(3);
        foreach ($displayTrainers as $index => $trainer) {
            $trainerName = $trainer->name ?? $trainer['name'] ?? 'N/A';
            $signatureImg = isset($data['trainer_signatures'][$index])
                ? "<img src=\"{$data['trainer_signatures'][$index]}\" style=\"width:50mm;height:15mm;\" />"
                : '<div style="height:15mm;"></div>';
            $signaturesHtml .= "
                <td style=\"width:25%;text-align:center;padding:5mm;\">
                    {$signatureImg}
                    <div style=\"border-top:1px solid #374151;padding-top:2mm;font-size:10pt;color:#1f2937;font-weight:500;\">{$trainerName}</div>
                    <div style=\"font-size:8pt;color:#6b7280;margin-top:1mm;\">วิทยากร</div>
                </td>";
        }

        if (!empty($data['authorized_signatory'])) {
            $authSignature = !empty($data['authorized_signature'])
                ? "<img src=\"{$data['authorized_signature']}\" style=\"width:50mm;height:15mm;\" />"
                : '<div style="height:15mm;"></div>';
            $signaturesHtml .= "
                <td style=\"width:25%;text-align:center;padding:5mm;\">
                    {$authSignature}
                    <div style=\"border-top:1px solid #374151;padding-top:2mm;font-size:10pt;color:#1f2937;font-weight:500;\">{$data['authorized_signatory']}</div>
                    <div style=\"font-size:8pt;color:#6b7280;margin-top:1mm;\">ผู้อำนวยการ</div>
                </td>";
        }

        // Organization logo
        $logoHtml = !empty($data['organization_logo'])
            ? "<img src=\"{$data['organization_logo']}\" style=\"width:50mm;height:auto;margin-bottom:3mm;\" />"
            : '';

        // Skills section
        $skillsHtml = !empty($data['skills']) ? "
            <div style=\"margin-top:4mm;text-align:left;max-width:80%;margin-left:auto;margin-right:auto;\">
                <div style=\"font-size:10pt;font-weight:600;color:#374151;margin-bottom:1mm;\">ความรู้และทักษะที่ได้รับ:</div>
                <div style=\"font-size:9pt;color:#6b7280;line-height:1.5;\">{$data['skills']}</div>
            </div>" : '';

        // Training details
        $detailsHtml = '';
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $totalHoursHtml = !empty($data['total_hours'])
                ? "<td style=\"width:33%;text-align:center;padding:2mm 0;\">
                    <div style=\"font-size:9pt;color:#6b7280;margin-bottom:1mm;\">รวมชั่วโมง</div>
                    <div style=\"font-size:12pt;font-weight:600;color:#1f2937;\">{$data['total_hours']} ชั่วโมง</div>
                </td>"
                : '';
            $detailsHtml = "
                <tr>
                    <td style=\"width:33%;text-align:center;padding:2mm 0;\">
                        <div style=\"font-size:9pt;color:#6b7280;margin-bottom:1mm;\">ระยะเวลาการอบรม</div>
                        <div style=\"font-size:12pt;font-weight:600;color:#1f2937;\">{$data['start_date']} - {$data['end_date']}</div>
                    </td>
                    {$totalHoursHtml}
                    <td style=\"width:33%;text-align:center;padding:2mm 0;\">
                        <div style=\"font-size:9pt;color:#6b7280;margin-bottom:1mm;\">วันที่ออกเอกสาร</div>
                        <div style=\"font-size:12pt;font-weight:600;color:#1f2937;\">{$data['issue_date']}</div>
                    </td>
                </tr>";
        }

        return "
        <style>
            body { font-family: freeserif, freesans, sans-serif; }
            .cert-title { font-size:26pt;font-weight:bold;color:#991b1b;letter-spacing:2px;text-align:center; }
            .cert-subtitle { font-size:14pt;color:#991b1b;font-style:italic;text-align:center;margin-top:1mm; }
            .intro-text { font-size:13pt;color:#374151;text-align:center;margin-bottom:4mm; }
            .trainee-name { font-size:" . (mb_strlen($data['trainee_name'] ?? '') > 40 ? '20pt' : (mb_strlen($data['trainee_name'] ?? '') > 30 ? '24pt' : '28pt')) . ";font-weight:bold;color:#1e3a8a;text-align:center;padding:2mm 0;border-bottom:2px solid #1e40af;display:inline-block;min-width:60%; }
            .program-name { font-size:16pt;font-weight:600;color:#1f2937;text-align:center;margin-bottom:2mm; }
            .program-desc { font-size:11pt;color:#6b7280;text-align:center;max-width:80%;margin:0 auto 2mm auto;line-height:1.5; }
            .org-name { font-size:18pt;font-weight:bold;color:#1e40af;text-align:center;margin-bottom:2mm; }
        </style>
        <div style=\"border:3px solid #1e40af;border-radius:10px;padding:10mm;height:277mm;position:relative;\">
            <div style=\"border:1px solid #93c5fd;padding:8mm;height:100%;\">
                <!-- Header -->
                <div style=\"text-align:center;margin-bottom:8mm;border-bottom:2px solid #e5e7eb;padding-bottom:5mm;\">
                    {$logoHtml}
                    <div class=\"org-name\">{$data['organization_name']}</div>
                </div>

                <!-- Title -->
                <div class=\"cert-title\">ใบประกาศนียบัตร</div>
                <div class=\"cert-subtitle\">Certificate of Completion</div>

                <!-- Content -->
                <div style=\"text-align:center;margin-top:5mm;\">
                    <div class=\"intro-text\">ขอมอบให้ไว้เพื่อแสดงว่า</div>
                    <div class=\"trainee-name\">{$data['trainee_name']}</div>
                    <div class=\"intro-text\" style=\"margin-top:4mm;\">ได้ผ่านการอบรมหลักสูตร</div>
                    <div style=\"margin:6mm 0;text-align:center;\">
                        <div class=\"program-name\">{$data['course_name']}</div>
                        " . (!empty($data['course_description']) ? "<div class=\"program-desc\">{$data['course_description']}</div>" : "") . "
                    </div>

                    <!-- Training Details -->
                    <div style=\"display:table;width:100%;margin:4mm 0;padding:2mm 0;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;\">
                        <table style=\"width:100%;border-collapse:collapse;\">
                            {$detailsHtml}
                        </table>
                    </div>

                    {$skillsHtml}
                </div>

                <!-- Footer -->
                <div style=\"position:absolute;bottom:10mm;left:15mm;right:15mm;display:table;width:calc(100% - 30mm);\">
                    <table style=\"width:100%;border-collapse:collapse;\">
                        <tr>
                            <td style=\"width:70%;\">
                                <table style=\"width:100%;border-collapse:collapse;\">
                                    <tr>{$signaturesHtml}</tr>
                                </table>
                            </td>
                            <td style=\"width:30%;text-align:center;vertical-align:bottom;padding:5mm;\">
                                <img src=\"{$qrCodeData}\" style=\"width:22mm;height:22mm;border:2px solid #e5e7eb;padding:1mm;border-radius:3px;\" />
                                <div style=\"font-size:8pt;color:#6b7280;margin-top:2mm;font-weight:600;\">{$data['certificate_code']}</div>
                                <div style=\"font-size:7pt;color:#9ca3af;\">ตรวจสอบความถูกต้อง</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>";
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
