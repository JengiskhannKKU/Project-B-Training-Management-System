<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Models\CertificateSequence;
use App\Models\Course;
use App\Models\TrainingSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateCertificatesToFixedTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:migrate-to-fixed
                            {--regenerate-codes : Regenerate certificate codes with new sequential format}
                            {--clear-files : Clear old PDF files to force regeneration}
                            {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing certificates to fixed template system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $regenerateCodes = $this->option('regenerate-codes');
        $clearFiles = $this->option('clear-files');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('📜 Starting certificate migration to fixed template system...');
        $this->newLine();

        $certificates = Certificate::with(['course', 'session', 'enrollment'])->get();

        if ($certificates->isEmpty()) {
            $this->info('✅ No certificates found. Nothing to migrate.');
            return Command::SUCCESS;
        }

        $this->info("Found {$certificates->count()} certificates to process");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($certificates->count());
        $progressBar->start();

        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($certificates as $certificate) {
            try {
                $updates = [];

                // Populate new fields with data from course and session
                if ($certificate->course && empty($certificate->description)) {
                    $updates['description'] = $certificate->course->description;
                }

                // Calculate total hours from session
                if ($certificate->session && empty($certificate->total_hours)) {
                    $totalHours = $this->calculateTotalHours($certificate->session);
                    if ($totalHours) {
                        $updates['total_hours'] = $totalHours;
                    }
                }

                // Set trainer IDs from session
                if ($certificate->session && empty($certificate->trainer_ids)) {
                    $updates['trainer_ids'] = [$certificate->session->trainer_id];
                }

                // Set default language if not set
                if (empty($certificate->language)) {
                    $updates['language'] = 'th';
                }

                // Set default organization if not set
                if (empty($certificate->organization_name)) {
                    $updates['organization_name'] = config('certificates.organization_name', 'KKU');
                }

                // Regenerate certificate codes if requested
                if ($regenerateCodes && (!$certificate->certificate_code || !$this->isNewFormatCode($certificate->certificate_code))) {
                    $newCode = $this->generateSequentialCode($certificate->issued_at);
                    $updates['certificate_code'] = $newCode;
                }

                // Clear old PDF files if requested
                if ($clearFiles && $certificate->file_data) {
                    $updates['file_data'] = null;
                    $updates['file_size'] = null;
                    $updates['generated_at'] = null;
                }

                // Apply updates if any
                if (!empty($updates) && !$isDryRun) {
                    $certificate->update($updates);
                    $updated++;
                }

                $progressBar->advance();
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'certificate_id' => $certificate->id,
                    'certificate_code' => $certificate->certificate_code,
                    'error' => $e->getMessage(),
                ];
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('📊 Migration Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Certificates', $certificates->count()],
                ['Successfully Updated', $updated],
                ['Failed', $failed],
            ]
        );

        if (!empty($errors)) {
            $this->newLine();
            $this->error('❌ Errors encountered:');
            $this->table(
                ['Certificate ID', 'Code', 'Error'],
                array_map(fn($e) => [$e['certificate_id'], $e['certificate_code'], $e['error']], $errors)
            );
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn('🔍 DRY RUN completed - No actual changes were made');
            $this->info('Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info('✅ Certificate migration completed successfully!');
        }

        return Command::SUCCESS;
    }

    /**
     * Calculate total training hours from session.
     */
    protected function calculateTotalHours(TrainingSession $session): ?int
    {
        if (!$session->start_date || !$session->end_date) {
            return null;
        }

        $days = $session->start_date->diffInDays($session->end_date) + 1;
        return $days * 8; // 8 hours per day
    }

    /**
     * Check if certificate code follows new sequential format.
     */
    protected function isNewFormatCode(string $code): bool
    {
        // New format: CERT-2026-0001
        return preg_match('/^CERT-\d{4}-\d+$/', $code) === 1;
    }

    /**
     * Generate sequential certificate code.
     */
    protected function generateSequentialCode(\DateTimeInterface $issuedAt): string
    {
        $year = $issuedAt->format('Y');
        $sequence = CertificateSequence::getNextSequence((int)$year);
        $padding = config('certificates.certificate_numbering.sequence_padding', 4);

        return sprintf('CERT-%d-%0' . $padding . 'd', $year, $sequence);
    }
}
