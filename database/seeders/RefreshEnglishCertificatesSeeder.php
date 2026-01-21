<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\CertificateSequence;
use App\Models\CertificateVerificationLog;
use App\Models\CertificateGenerationBatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RefreshEnglishCertificatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting to refresh certificate data...');

        // 1. Clear all certificate-related data
        $this->command->info('Clearing existing certificate data...');
        $this->clearCertificateData();

        // 2. Reset certificate sequences
        $this->command->info('Resetting certificate sequences...');
        $this->resetCertificateSequences();

        // 3. Create new English certificates
        $this->command->info('Creating new English certificates...');
        $certificatesCreated = $this->createEnglishCertificates();

        $this->command->info("Certificate refresh completed. Created {$certificatesCreated} English certificates (A4 Landscape).");
    }

    /**
     * Clear all certificate-related data from the database.
     */
    private function clearCertificateData(): void
    {
        // Disable foreign key constraints for SQLite
        DB::statement('PRAGMA foreign_keys = OFF;');

        // Clear certificate verification logs
        CertificateVerificationLog::query()->delete();

        // Clear certificate generation batches
        CertificateGenerationBatch::query()->delete();

        // Clear certificates
        Certificate::query()->delete();

        // Re-enable foreign key constraints for SQLite
        DB::statement('PRAGMA foreign_keys = ON;');

        $this->command->info('Certificate data cleared successfully.');
    }

    /**
     * Reset certificate sequences for the current year.
     */
    private function resetCertificateSequences(): void
    {
        $currentYear = (int) date('Y');

        // Reset or create the sequence for current year
        CertificateSequence::updateOrCreate(
            ['year' => $currentYear],
            ['last_sequence' => 0]
        );

        $this->command->info("Certificate sequences reset for year {$currentYear}.");
    }

    /**
     * Create new English certificates for completed enrollments.
     */
    private function createEnglishCertificates(): int
    {
        // Get admin user for issuing certificates
        $admin = User::where('email', 'admin@example.com')->first();

        if (!$admin) {
            $this->command->warn('Admin user not found. Skipping certificate creation.');
            return 0;
        }

        // Find completed enrollments with all necessary relationships
        $completedEnrollments = Enrollment::whereNotNull('completed_at')
            ->where('status', 'completed')
            ->with([
                'user',
                'session',
                'session.course',
                'session.trainer',
                'session.sessionDays'
            ])
            ->get();

        if ($completedEnrollments->isEmpty()) {
            $this->command->warn('No completed enrollments found for certificate creation.');
            return 0;
        }

        $certificatesCreated = 0;
        $currentYear = (int) date('Y');

        foreach ($completedEnrollments as $enrollment) {
            $session = $enrollment->session;
            $course = $session->course;
            $user = $enrollment->user;
            $trainer = $session->trainer;

            // Calculate total hours from session days
            $totalHours = $this->calculateTotalHours($session);

            // Get training period dates
            [$startDate, $endDate] = $this->getTrainingDates($session);

            // Get next certificate code
            $certificateCode = $this->getNextCertificateCode($currentYear);

            // Generate QR code URL
            $qrCodeUrl = url("/verify/{$certificateCode}");

            // Create certificate record
            Certificate::create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'session_id' => $session->id,
                'issued_by' => $admin->id,
                'issued_at' => Carbon::parse($enrollment->completed_at)->addDay(),
                'certificate_code' => $certificateCode,
                'language' => 'en', // English certificate (A4 Landscape)
                'status' => 'valid',

                // Organization details
                'organization_name' => config('certificates.organization_name', 'Training Management System'),
                'organization_logo_url' => config('certificates.organization_logo_url'),

                // Certificate content
                'description' => $course->description,
                'total_hours' => $totalHours,

                // Score (randomized for demo purposes)
                'score' => $this->generateRandomScore(),

                // Trainer information
                'trainer_ids' => $trainer ? [$trainer->id] : [],
                'trainer_signatures' => $trainer ? [
                    [
                        'name' => $trainer->name,
                        'title' => 'Course Instructor',
                    ]
                ] : [],

                // Authorized signatory
                'authorized_signatory_name' => config('certificates.authorized_signatory_name', 'John Smith'),
                'authorized_signature_url' => config('certificates.authorized_signature_url'),

                // QR code
                'qr_code_url' => $qrCodeUrl,

                // File data (will be generated on demand using lazy generation)
                'file_url' => null,
                'file_data' => null,
                'file_mime_type' => 'application/pdf',
                'file_size' => 0,
                'generated_at' => null,
            ]);

            $certificatesCreated++;
        }

        return $certificatesCreated;
    }

    /**
     * Calculate total training hours from session days.
     */
    private function calculateTotalHours($session): int
    {
        $totalMinutes = 0;

        foreach ($session->sessionDays as $day) {
            if ($day->start_time && $day->end_time) {
                $start = Carbon::parse($day->start_time);
                $end = Carbon::parse($day->end_time);
                $totalMinutes += $end->diffInMinutes($start);
            }
        }

        return (int) ceil($totalMinutes / 60); // Convert to hours
    }

    /**
     * Get start and end dates for training period.
     */
    private function getTrainingDates($session): array
    {
        $firstDay = $session->sessionDays->sortBy('date')->first();
        $lastDay = $session->sessionDays->sortByDesc('date')->first();

        $startDate = $firstDay ? Carbon::parse($firstDay->date)->format('M d, Y') : null;
        $endDate = $lastDay ? Carbon::parse($lastDay->date)->format('M d, Y') : null;

        return [$startDate, $endDate];
    }

    /**
     * Get the next certificate code in sequence.
     */
    private function getNextCertificateCode(int $year): string
    {
        $sequence = CertificateSequence::where('year', $year)->first();

        if (!$sequence) {
            $sequence = CertificateSequence::create([
                'year' => $year,
                'last_sequence' => 0,
            ]);
        }

        $sequence->increment('last_sequence');
        $sequenceNumber = $sequence->last_sequence;

        return sprintf('CERT-%d-%04d', $year, $sequenceNumber);
    }

    /**
     * Generate a random score between 75 and 100.
     */
    private function generateRandomScore(): float
    {
        return (float) rand(7500, 10000) / 100;
    }
}
