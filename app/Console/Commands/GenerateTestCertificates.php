<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateTestCertificates extends Command
{
    protected $signature = 'certificates:generate-test {count=3}';
    protected $description = 'Generate test certificates for development';

    public function handle(): int
    {
        $count = (int) $this->argument('count');

        // Get enrollments with completed status or any status
        $enrollments = Enrollment::with(['user', 'session.course', 'session.trainer'])
            ->whereDoesntHave('certificate')
            ->take($count)
            ->get();

        if ($enrollments->isEmpty()) {
            $this->warn('No enrollments without certificates found. Getting any enrollments...');
            $enrollments = Enrollment::with(['user', 'session.course', 'session.trainer'])
                ->take($count)
                ->get();
        }

        if ($enrollments->isEmpty()) {
            $this->error('No enrollments found in database.');
            return 1;
        }

        $admin = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->first();

        $this->info("Generating certificates for {$enrollments->count()} enrollments...");

        $created = 0;
        foreach ($enrollments as $index => $enrollment) {
            $session = $enrollment->session;
            $course = $session?->course;
            $user = $enrollment->user;
            $trainerId = $session?->trainer_id ?? $admin?->id ?? $user->id;

            // Skip if certificate already exists for this enrollment
            if (Certificate::where('enrollment_id', $enrollment->id)->exists()) {
                $this->line("  Skipped: Certificate already exists for enrollment #{$enrollment->id}");
                continue;
            }

            $certificate = Certificate::create([
                'certificate_code' => 'CERT-' . strtoupper(Str::random(8)),
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'course_id' => $course?->id,
                'session_id' => $session?->id,
                'issued_by' => $admin?->id ?? $user->id,
                'issued_at' => now()->subDays($index * 3),
                'status' => 'valid',
                'language' => 'en',
                'total_hours' => rand(8, 40),
                'description' => $course?->description ?? 'Professional training program with comprehensive curriculum.',
                'organization_name' => 'KKU Training Center',
                'authorized_signatory_name' => 'Dr. Somchai Prasert',
                'trainer_ids' => [$trainerId],
                'skills' => 'Problem solving, Critical thinking, Professional development',
            ]);

            $courseName = $course?->title ?? 'Unknown Course';
            $this->line("  Created: {$certificate->certificate_code} for {$user->name} ({$courseName})");
            $created++;
        }

        $this->info("Successfully created {$created} test certificates.");

        return 0;
    }
}
