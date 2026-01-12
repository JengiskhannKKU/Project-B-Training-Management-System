<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        // Get admin user for issuing certificates
        $admin = User::where('email', 'admin@example.com')->first();

        // Find completed enrollments
        $completedEnrollments = Enrollment::whereNotNull('completed_at')
            ->where('status', 'completed')
            ->with(['session', 'session.course'])
            ->get();

        if ($completedEnrollments->isEmpty()) {
            $this->command->warn('No completed enrollments found for certificate seeding.');
            return;
        }

        $globalTemplate = CertificateTemplate::where('scope', 'global')->first();
        $certificatesCreated = 0;

        foreach ($completedEnrollments as $enrollment) {
            // Find specific template or global
            $template = CertificateTemplate::where('course_id', $enrollment->session->course_id)
                ->first() ?? $globalTemplate;

            // Randomly decide if certificate is issued (90% chance)
            if (rand(0, 100) > 10) {
                $issuedAt = $enrollment->completed_at
                    ? Carbon::parse($enrollment->completed_at)->addDay()
                    : Carbon::now();

                Certificate::updateOrCreate(
                    ['enrollment_id' => $enrollment->id],
                    [
                        'user_id' => $enrollment->user_id,
                        'course_id' => $enrollment->session->course_id,
                        'session_id' => $enrollment->session_id,
                        'template_id' => $template?->id,
                        'issued_by' => $admin?->id,
                        'issued_at' => $issuedAt,
                        'certificate_code' => 'CERT-' . strtoupper(\Illuminate\Support\Str::random(12)),
                        'file_url' => null,
                        'status' => 'valid',
                    ]
                );
                $certificatesCreated++;
            }
        }

        // Ensure at least one revoked certificate for testing
        $allCertificates = Certificate::all();
        if ($allCertificates->count() > 1) {
            $lastCert = $allCertificates->last();
            $lastCert->update([
                'status' => 'revoked',
                'revoked_by' => $admin?->id,
                'revoked_at' => Carbon::now(),
                'revoked_note' => 'Revoked for testing purposes',
            ]);
        }

        $this->command->info('Certificates seeded: ' . $certificatesCreated);
    }
}
