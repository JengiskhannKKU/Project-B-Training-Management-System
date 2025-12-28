<?php

namespace Database\Seeders;

use App\Models\Certificate;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        $certificates = [
            // Certificates for Session 3 - Data Science (Completed)
            [
                'enrollment_id' => 6,
                'user_id' => 6,
                'program_id' => 3,
                'session_id' => 3,
                'issued_by' => 1,
                'issued_at' => Carbon::now()->subDays(7),
                'certificate_code' => 'CERT-DS-2025-001',
                'file_url' => 'certificates/CERT-DS-2025-001.pdf',
                'status' => 'valid',
            ],
            [
                'enrollment_id' => 7,
                'user_id' => 7,
                'program_id' => 3,
                'session_id' => 3,
                'issued_by' => 1,
                'issued_at' => Carbon::now()->subDays(7),
                'certificate_code' => 'CERT-DS-2025-002',
                'file_url' => 'certificates/CERT-DS-2025-002.pdf',
                'status' => 'valid',
            ],
            [
                'enrollment_id' => 8,
                'user_id' => 8,
                'program_id' => 3,
                'session_id' => 3,
                'issued_by' => 1,
                'issued_at' => Carbon::now()->subDays(7),
                'certificate_code' => 'CERT-DS-2025-003',
                'file_url' => 'certificates/CERT-DS-2025-003.pdf',
                'status' => 'valid',
            ],
            [
                'enrollment_id' => 9,
                'user_id' => 9,
                'program_id' => 3,
                'session_id' => 3,
                'issued_by' => 1,
                'issued_at' => Carbon::now()->subDays(7),
                'certificate_code' => 'CERT-DS-2025-004',
                'file_url' => 'certificates/CERT-DS-2025-004.pdf',
                'status' => 'valid',
            ],

            // Certificates for Session 6 - Web Dev Winter (Completed)
            [
                'enrollment_id' => 11,
                'user_id' => 5,
                'program_id' => 1,
                'session_id' => 6,
                'issued_by' => 1,
                'issued_at' => Carbon::now()->subDays(47),
                'certificate_code' => 'CERT-WEB-2024-001',
                'file_url' => 'certificates/CERT-WEB-2024-001.pdf',
                'status' => 'valid',
            ],
            [
                'enrollment_id' => 12,
                'user_id' => 10,
                'program_id' => 1,
                'session_id' => 6,
                'issued_by' => 1,
                'issued_at' => Carbon::now()->subDays(47),
                'certificate_code' => 'CERT-WEB-2024-002',
                'file_url' => 'certificates/CERT-WEB-2024-002.pdf',
                'status' => 'revoked',
            ],
        ];

        foreach ($certificates as $certificate) {
            Certificate::create($certificate);
        }
    }
}
