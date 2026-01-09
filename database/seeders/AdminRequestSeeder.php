<?php


namespace Database\Seeders;

use App\Models\AdminRequest;
use App\Models\User;
use App\Models\Program;
use App\Models\TrainingSession;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AdminRequestSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstWhere('email', 'admin@example.com');
        $trainer = User::whereHas('role', fn($q) => $q->where('name', 'trainer'))->first();

        $program = Program::first();
        $session = TrainingSession::first();

        if (!$trainer)
            return;

        $requests = [
            [
                'requester_id' => $trainer->id,
                'target_type' => 'program',
                'action' => 'create',
                'target_id' => null,
                'payload' => json_encode([
                    'name' => 'Blockchain Development',
                    'code' => 'BLOCK-101',
                    'description' => 'Introduction to blockchain technology and smart contracts.',
                    'category' => 'Blockchain',
                    'duration_hours' => 45,
                ]),
                'status' => 'pending',
                'admin_note' => null,
                'resolved_by' => null,
                'resolved_at' => null,
            ],
            [
                'requester_id' => $trainer->id,
                'target_type' => 'session',
                'action' => 'create',
                'target_id' => null,
                'payload' => json_encode([
                    'program_id' => $program?->id ?? 1,
                    'title' => 'Data Science with Python - Summer 2025',
                    'start_date' => '2025-06-01',
                    'end_date' => '2025-06-20',
                    'capacity' => 30,
                ]),
                'status' => 'approved',
                'admin_note' => 'Approved for summer session.',
                'resolved_by' => $admin->id ?? 1,
                'resolved_at' => Carbon::now()->subDays(2),
            ],
            [
                'requester_id' => $trainer->id,
                'target_type' => 'program',
                'action' => 'update',
                'target_id' => $program?->id,
                'payload' => json_encode([
                    'duration_hours' => 60,
                    'description' => 'Extended cybersecurity course with advanced topics.',
                ]),
                'status' => 'rejected',
                'admin_note' => 'Duration change not approved. Please submit a new program instead.',
                'resolved_by' => $admin->id ?? 1,
                'resolved_at' => Carbon::now()->subDays(5),
            ],
        ];

        foreach ($requests as $request) {
            AdminRequest::create($request);
        }
    }
}

