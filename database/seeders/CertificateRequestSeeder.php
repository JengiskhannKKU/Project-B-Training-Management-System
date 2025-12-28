<?php

namespace Database\Seeders;

use App\Models\CertificateRequest;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CertificateRequestSeeder extends Seeder
{
    public function run(): void
    {
        $requests = [
            [
                'trainer_id' => 3,
                'program_id' => 3,
                'session_id' => 3,
                'type' => 'session',
                'status' => 'approved',
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(8),
                'note' => 'Request for Data Science Winter 2025 session certificates.',
            ],
            [
                'trainer_id' => 2,
                'program_id' => 1,
                'session_id' => 6,
                'type' => 'session',
                'status' => 'approved',
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(48),
                'note' => 'Request for Web Dev Winter 2024 session certificates.',
            ],
            [
                'trainer_id' => 2,
                'program_id' => 2,
                'session_id' => null,
                'type' => 'program',
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'note' => 'Requesting program-level certificates for React Development.',
            ],
            [
                'trainer_id' => 4,
                'program_id' => 4,
                'session_id' => null,
                'type' => 'program',
                'status' => 'rejected',
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(2),
                'note' => 'Program not yet completed enough times to warrant program certificates.',
            ],
        ];

        foreach ($requests as $request) {
            CertificateRequest::create($request);
        }
    }
}
