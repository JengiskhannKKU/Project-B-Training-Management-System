<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupCertificateFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:cleanup
                          {--dry-run : Show what would be cleaned without actually deleting}
                          {--force : Force cleanup even if disabled in config}
                          {--status=* : Only clean certificates with specific status (default: revoked)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old certificate files to manage database size (KAN-395)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $enabled = config('certificates.cleanup.enabled', false);
        $force = $this->option('force');

        if (!$enabled && !$force) {
            $this->warn('Certificate cleanup is disabled in configuration.');
            $this->info('Use --force to run anyway, or enable in config/certificates.php');
            return Command::FAILURE;
        }

        $retentionDays = config('certificates.cleanup.retention_days', 365);
        $defaultStatuses = config('certificates.cleanup.cleanup_statuses', ['revoked']);
        $statuses = $this->option('status') ?: $defaultStatuses;
        $dryRun = $this->option('dry-run');

        $this->info('Certificate File Cleanup');
        $this->info('========================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be deleted');
            $this->newLine();
        }

        $this->info("Retention period: {$retentionDays} days");
        $this->info('Target statuses: ' . implode(', ', $statuses));
        $this->newLine();

        // Find certificates eligible for cleanup
        $query = Certificate::query()
            ->whereNotNull('file_data')
            ->whereIn('status', $statuses)
            ->where('generated_at', '<', now()->subDays($retentionDays));

        $count = $query->count();

        if ($count === 0) {
            $this->info('No certificate files found for cleanup.');
            return Command::SUCCESS;
        }

        // Calculate total size before cleanup
        $totalSize = $this->calculateTotalSize($query);
        $totalSizeMB = round($totalSize / 1024 / 1024, 2);

        $this->info("Found {$count} certificate(s) eligible for cleanup");
        $this->info("Total size to be freed: {$totalSizeMB} MB");
        $this->newLine();

        if (!$dryRun && !$this->confirm('Do you want to proceed with cleanup?', true)) {
            $this->info('Cleanup cancelled.');
            return Command::SUCCESS;
        }

        // Perform cleanup
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $cleaned = 0;
        $errors = 0;

        $query->chunk(100, function ($certificates) use (&$cleaned, &$errors, $bar, $dryRun) {
            foreach ($certificates as $certificate) {
                try {
                    if (!$dryRun) {
                        $certificate->update([
                            'file_data' => null,
                            'file_mime_type' => null,
                            'file_size' => null,
                            'generated_at' => null,
                        ]);
                    }
                    $cleaned++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("\nFailed to clean certificate {$certificate->id}: {$e->getMessage()}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Show results
        $this->info('Cleanup completed!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Certificates processed', $cleaned],
                ['Errors', $errors],
                ['Space freed', "{$totalSizeMB} MB"],
                ['Mode', $dryRun ? 'Dry Run' : 'Live'],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Calculate total size of certificates in query.
     */
    private function calculateTotalSize($query): int
    {
        return $query->sum(DB::raw('LENGTH(file_data)')) ?? 0;
    }
}
