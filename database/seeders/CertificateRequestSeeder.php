<?php


namespace Database\Seeders;

use App\Models\CertificateRequest;
use App\Models\User;
use App\Models\Program;
use App\Models\TrainingSession;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CertificateRequestSeeder extends Seeder
{
    public function run(): void
    {
        $trainer = User::whereHas('role', fn($q) => $q->where('name', 'trainer'))->first();
        $admin = User::firstWhere('email', 'admin@example.com');

        $program = Program::first();
        $session = TrainingSession::whereNotNull('completed_at')->first(); // Completed session

        if (!$trainer || !$program)
            return;

        $requests = [
            // Approved session request
            [
                'trainer_id' => $trainer->id,
                'program_id' => $program->id,
                'session_id' => $session?->id,
                'type' => 'session',
                'status' => 'approved',
                'approved_by' => $admin->id ?? 1,
                'approved_at' => Carbon::now()->subDays(8),
                'note' => 'Request for session certificates.',
            ],
            // Pending program request
            [
                'trainer_id' => $trainer->id,
                'program_id' => $program->id,
                'session_id' => null,
                'type' => 'program',
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'note' => 'Requesting program-level certificates.',
            ],
            // Rejected request
            [
                'trainer_id' => $trainer->id,
                'program_id' => $program->id,
                'session_id' => null,
                'type' => 'program',
                'status' => 'rejected',
                'approved_by' => $admin->id ?? 1,
                'approved_at' => Carbon::now()->subDays(2),
                'note' => 'Not eligible yet.',
            ],
        ];

        foreach ($requests as $request) {
            // Check if session_id is required for type='session'
            if ($request['type'] === 'session' && !$request['session_id'])
                continue;

            CertificateRequest::create($request);
        }
    }
}

