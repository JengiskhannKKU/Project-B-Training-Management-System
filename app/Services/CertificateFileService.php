<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CertificateFileService
{
    public function __construct(
        protected CertificateRenderer $renderer
    ) {
    }

    /**
     * Generate and store PDF file (lazy generation - only if not already stored).
     *
     * @param Certificate $certificate
     * @return Certificate
     */
    public function generateAndStoreFile(Certificate $certificate): Certificate
    {
        // Skip if already generated
        if ($this->hasFileData($certificate)) {
            return $certificate;
        }

        return $this->forceGenerateAndStoreFile($certificate);
    }

    /**
     * Force regenerate and store PDF file.
     *
     * @param Certificate $certificate
     * @return Certificate
     * @throws RuntimeException
     */
    public function forceGenerateAndStoreFile(Certificate $certificate): Certificate
    {
        try {
            // Render PDF using the fixed template
            $pdfData = $this->renderer->render($certificate);
            $fileSize = strlen($pdfData);

            // Validate file size
            $this->validateFileSize($fileSize, $certificate);

            // Store in database
            $certificate->forceFill([
                'file_data' => $pdfData,
                'file_mime_type' => 'application/pdf',
                'file_size' => $fileSize,
                'generated_at' => now(),
            ])->save();

            Log::info('Certificate PDF generated and stored', [
                'certificate_id' => $certificate->id,
                'certificate_code' => $certificate->certificate_code,
                'file_size_kb' => round($fileSize / 1024, 2),
            ]);

            return $certificate->fresh();
        } catch (\Exception $e) {
            Log::error('Certificate file generation failed', [
                'certificate_id' => $certificate->id,
                'certificate_code' => $certificate->certificate_code,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                "Failed to generate certificate file: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Get PDF file data (generate if needed).
     *
     * @param Certificate $certificate
     * @return string Binary PDF data
     */
    public function getFileData(Certificate $certificate): string
    {
        if (!$this->hasFileData($certificate)) {
            $certificate = $this->generateAndStoreFile($certificate);
        }

        return $certificate->file_data;
    }

    /**
     * Check if certificate has file data stored.
     *
     * @param Certificate $certificate
     * @return bool
     */
    public function hasFileData(Certificate $certificate): bool
    {
        return !empty($certificate->file_data) && !empty($certificate->generated_at);
    }

    /**
     * Delete stored file data to save space.
     *
     * @param Certificate $certificate
     * @return bool
     */
    public function deleteFileData(Certificate $certificate): bool
    {
        if (!$this->hasFileData($certificate)) {
            return true;
        }

        $certificate->forceFill([
            'file_data' => null,
            'file_size' => null,
            'generated_at' => null,
        ])->save();

        Log::info('Certificate file data deleted', [
            'certificate_id' => $certificate->id,
            'certificate_code' => $certificate->certificate_code,
        ]);

        return true;
    }

    /**
     * Validate certificate file size against configured limits.
     *
     * @param int $fileSizeBytes File size in bytes
     * @param Certificate $certificate
     * @throws RuntimeException if file size exceeds hard limit
     */
    protected function validateFileSize(int $fileSizeBytes, Certificate $certificate): void
    {
        $fileSizeKB = round($fileSizeBytes / 1024, 2);
        $maxSizeKB = config('certificates.max_file_sizes.certificate_file', 2048);
        $logLargeFiles = config('certificates.monitoring.log_large_files', true);

        // Log warning if file is large (>50% of limit)
        $warningThresholdKB = $maxSizeKB * 0.5;
        if ($logLargeFiles && $fileSizeKB > $warningThresholdKB) {
            Log::warning('Large certificate file generated', [
                'certificate_id' => $certificate->id,
                'certificate_code' => $certificate->certificate_code,
                'file_size_kb' => $fileSizeKB,
                'max_size_kb' => $maxSizeKB,
                'language' => $certificate->language,
            ]);
        }

        // Throw exception if file exceeds maximum size
        if ($fileSizeKB > $maxSizeKB) {
            throw new RuntimeException(
                "Certificate file size ({$fileSizeKB} KB) exceeds maximum allowed size ({$maxSizeKB} KB). " .
                "Consider reducing image sizes or simplifying content."
            );
        }
    }

    /**
     * Get file size statistics for certificates.
     *
     * @return array
     */
    public function getFileStatistics(): array
    {
        $stats = Certificate::selectRaw('
            COUNT(*) as total_certificates,
            COUNT(file_data) as certificates_with_files,
            SUM(file_size) as total_size_bytes,
            AVG(file_size) as avg_size_bytes,
            MIN(file_size) as min_size_bytes,
            MAX(file_size) as max_size_bytes
        ')->first();

        return [
            'total_certificates' => $stats->total_certificates ?? 0,
            'certificates_with_files' => $stats->certificates_with_files ?? 0,
            'total_size_mb' => round(($stats->total_size_bytes ?? 0) / 1024 / 1024, 2),
            'avg_size_kb' => round(($stats->avg_size_bytes ?? 0) / 1024, 2),
            'min_size_kb' => round(($stats->min_size_bytes ?? 0) / 1024, 2),
            'max_size_kb' => round(($stats->max_size_bytes ?? 0) / 1024, 2),
            'max_allowed_kb' => config('certificates.max_file_sizes.certificate_file', 2048),
        ];
    }

    /**
     * Cleanup old revoked certificate files to save space.
     *
     * @param int $daysOld Only cleanup files older than this many days
     * @return int Number of files cleaned up
     */
    public function cleanupRevokedCertificateFiles(int $daysOld = 365): int
    {
        if (!config('certificates.cleanup.enabled', false)) {
            Log::info('Certificate cleanup is disabled in config');
            return 0;
        }

        $count = 0;
        $cutoffDate = now()->subDays($daysOld);

        $certificates = Certificate::where('status', 'revoked')
            ->where('revoked_at', '<', $cutoffDate)
            ->whereNotNull('file_data')
            ->get();

        foreach ($certificates as $certificate) {
            try {
                $this->deleteFileData($certificate);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to cleanup certificate file', [
                    'certificate_id' => $certificate->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Certificate file cleanup completed', [
            'files_cleaned' => $count,
            'days_old' => $daysOld,
        ]);

        return $count;
    }

    /**
     * Regenerate all certificates (for template changes or fixes).
     * Use with caution - this is resource-intensive.
     *
     * @param array $certificateIds Specific certificate IDs to regenerate (empty = all)
     * @param bool $onlyWithoutFiles Only regenerate certificates without files
     * @return array Statistics
     */
    public function regenerateMultiple(array $certificateIds = [], bool $onlyWithoutFiles = false): array
    {
        $query = Certificate::query();

        if (!empty($certificateIds)) {
            $query->whereIn('id', $certificateIds);
        }

        if ($onlyWithoutFiles) {
            $query->whereNull('file_data');
        }

        $certificates = $query->get();
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($certificates as $certificate) {
            try {
                $this->forceGenerateAndStoreFile($certificate);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'certificate_id' => $certificate->id,
                    'certificate_code' => $certificate->certificate_code,
                    'error' => $e->getMessage(),
                ];

                Log::error('Certificate regeneration failed', [
                    'certificate_id' => $certificate->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'total' => $certificates->count(),
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
