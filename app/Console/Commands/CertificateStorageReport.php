<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CertificateStorageReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:storage-report
                          {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a report on certificate storage usage (KAN-394)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $data = $this->gatherStorageData();

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $this->displayReport($data);

        return Command::SUCCESS;
    }

    /**
     * Gather storage data.
     */
    private function gatherStorageData(): array
    {
        // Certificate statistics
        $totalCertificates = Certificate::count();
        $certificatesWithFiles = Certificate::whereNotNull('file_data')->count();
        $certificatesWithoutFiles = $totalCertificates - $certificatesWithFiles;

        // Storage sizes
        $certificateStorageBytes = Certificate::sum(DB::raw('LENGTH(file_data)')) ?? 0;
        $templateStorageBytes = CertificateTemplate::sum(DB::raw('LENGTH(background_image)')) ?? 0;
        $totalStorageBytes = $certificateStorageBytes + $templateStorageBytes;

        $certificateStorageMB = round($certificateStorageBytes / 1024 / 1024, 2);
        $templateStorageMB = round($templateStorageBytes / 1024 / 1024, 2);
        $totalStorageMB = round($totalStorageBytes / 1024 / 1024, 2);

        // Average file sizes
        $avgCertificateSizeKB = $certificatesWithFiles > 0
            ? round($certificateStorageBytes / $certificatesWithFiles / 1024, 2)
            : 0;

        // Template statistics
        $totalTemplates = CertificateTemplate::count();
        $templatesWithBackground = CertificateTemplate::whereNotNull('background_image')->count();

        // Status breakdown
        $statusBreakdown = Certificate::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get largest certificates
        $largestCertificates = Certificate::select('id', 'certificate_code', 'status', 'file_size')
            ->whereNotNull('file_data')
            ->orderByDesc('file_size')
            ->limit(5)
            ->get()
            ->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'code' => $cert->certificate_code,
                    'status' => $cert->status,
                    'size_kb' => round($cert->file_size / 1024, 2),
                ];
            })
            ->toArray();

        return [
            'certificates' => [
                'total' => $totalCertificates,
                'with_files' => $certificatesWithFiles,
                'without_files' => $certificatesWithoutFiles,
                'storage_percentage' => $totalCertificates > 0
                    ? round(($certificatesWithFiles / $totalCertificates) * 100, 2)
                    : 0,
            ],
            'storage' => [
                'certificates_mb' => $certificateStorageMB,
                'templates_mb' => $templateStorageMB,
                'total_mb' => $totalStorageMB,
                'avg_certificate_kb' => $avgCertificateSizeKB,
            ],
            'templates' => [
                'total' => $totalTemplates,
                'with_background' => $templatesWithBackground,
            ],
            'status_breakdown' => $statusBreakdown,
            'largest_certificates' => $largestCertificates,
            'configuration' => [
                'storage_policy' => config('certificates.storage_policy', 'on_demand'),
                'default_generation' => config('certificates.default_generation_mode', 'lazy'),
                'max_file_size_kb' => config('certificates.max_file_sizes.certificate_file', 2048),
                'max_background_size_kb' => config('certificates.max_file_sizes.background_image', 5120),
                'cleanup_enabled' => config('certificates.cleanup.enabled', false),
            ],
            'recommendations' => $this->getRecommendations($totalCertificates, $totalStorageMB),
        ];
    }

    /**
     * Display the report.
     */
    private function displayReport(array $data): void
    {
        $this->info('Certificate Storage Report');
        $this->info('=========================');
        $this->newLine();

        // Overview
        $this->info('Overview');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Certificates', number_format($data['certificates']['total'])],
                ['With PDF Files', number_format($data['certificates']['with_files']) . ' (' . $data['certificates']['storage_percentage'] . '%)'],
                ['Without PDF Files', number_format($data['certificates']['without_files'])],
            ]
        );
        $this->newLine();

        // Storage
        $this->info('Storage Usage');
        $this->table(
            ['Type', 'Size'],
            [
                ['Certificate PDFs', $data['storage']['certificates_mb'] . ' MB'],
                ['Template Backgrounds', $data['storage']['templates_mb'] . ' MB'],
                ['Total Storage', $data['storage']['total_mb'] . ' MB'],
                ['Avg Certificate Size', $data['storage']['avg_certificate_kb'] . ' KB'],
            ]
        );
        $this->newLine();

        // Configuration
        $this->info('Current Configuration');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Storage Policy', $data['configuration']['storage_policy']],
                ['Default Generation', $data['configuration']['default_generation']],
                ['Max Certificate Size', $data['configuration']['max_file_size_kb'] . ' KB'],
                ['Max Background Size', $data['configuration']['max_background_size_kb'] . ' KB'],
                ['Cleanup Enabled', $data['configuration']['cleanup_enabled'] ? 'Yes' : 'No'],
            ]
        );
        $this->newLine();

        // Status breakdown
        if (!empty($data['status_breakdown'])) {
            $this->info('Certificate Status Breakdown');
            $statusTable = [];
            foreach ($data['status_breakdown'] as $status => $count) {
                $statusTable[] = [$status, number_format($count)];
            }
            $this->table(['Status', 'Count'], $statusTable);
            $this->newLine();
        }

        // Largest certificates
        if (!empty($data['largest_certificates'])) {
            $this->info('Largest Certificate Files (Top 5)');
            $this->table(
                ['ID', 'Code', 'Status', 'Size (KB)'],
                array_map(function ($cert) {
                    return [
                        $cert['id'],
                        $cert['code'],
                        $cert['status'],
                        number_format($cert['size_kb'], 2),
                    ];
                }, $data['largest_certificates'])
            );
            $this->newLine();
        }

        // Recommendations
        if (!empty($data['recommendations'])) {
            $this->info('Recommendations (KAN-394)');
            foreach ($data['recommendations'] as $recommendation) {
                $this->line("  • {$recommendation}");
            }
            $this->newLine();
        }
    }

    /**
     * Get storage recommendations based on usage.
     */
    private function getRecommendations(int $totalCertificates, float $totalStorageMB): array
    {
        $recommendations = [];

        // Size-based recommendations
        if ($totalCertificates < 10000) {
            $recommendations[] = "Small deployment detected. Consider 'always' storage policy for best performance.";
        } elseif ($totalCertificates < 100000) {
            $recommendations[] = "Medium deployment detected. 'on_demand' storage policy is recommended.";
            if ($totalStorageMB > 500) {
                $recommendations[] = "Consider enabling cleanup for revoked certificates to save space.";
            }
        } else {
            $recommendations[] = "Large deployment detected. Use 'on_demand' or 'temporary' storage policy.";
            $recommendations[] = "Consider implementing aggressive cleanup policies.";
            if ($totalStorageMB > 1000) {
                $recommendations[] = "Database storage exceeds 1GB. Consider external storage solutions (S3, etc.).";
            }
        }

        // Warning threshold check
        $warningThreshold = config('certificates.monitoring.storage_warning_threshold', 1000);
        if ($totalStorageMB > $warningThreshold) {
            $recommendations[] = "WARNING: Storage usage ({$totalStorageMB} MB) exceeds threshold ({$warningThreshold} MB).";
        }

        return $recommendations;
    }
}
