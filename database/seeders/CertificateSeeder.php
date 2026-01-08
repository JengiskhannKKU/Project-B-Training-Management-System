<?php


namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        // Find completed enrollments
        $completedEnrollments = Enrollment::whereNotNull('completed_at')
            ->where('status', 'completed')
            ->with(['session', 'session.program'])
            ->get();

        $globalTemplate = CertificateTemplate::where('scope', 'global')->first();

        foreach ($completedEnrollments as $enrollment) {
            // Find specific template or global
            $template = CertificateTemplate::where('program_id', $enrollment->session->program_id)->first() ?? $globalTemplate;

            // Randomly decide if certificate is issued provided it's completed (most should be)
            if (rand(0, 100) > 10) { // 90% chance
                Certificate::updateOrCreate(
                    ['enrollment_id' => $enrollment->id],
                    [
                        'user_id' => $enrollment->user_id,
                        'program_id' => $enrollment->session->program_id,
                        'session_id' => $enrollment->session_id,
                        'template_id' => $template?->id,
                        'issued_by' => 1, // Assuming admin ID 1 exists, or could fetch.
                        'issued_at' => $enrollment->completed_at->addDays(1),
                        'certificate_code' => 'CERT-' . strtoupper(\Illuminate\Support\Str::random(8)),
                        'file_url' => 'certificates/demo.pdf', // Mock
                        'status' => 'valid',
                    ]
                );
            }
        }

        // Ensure at least one revoked certificate for testing
        // Removed duplicate creation block to avoid integrity violation

        // Actually, let's just make the last one revoked if we have multiple
        $validCert = Certificate::first();
        $lastCert = Certificate::latest()->first();
        if ($lastCert && $lastCert->id !== $validCert?->id) {
            $lastCert->update(['status' => 'revoked']);
        }
    }
}

