<?php

namespace Database\Seeders;

use App\Models\AdminRequest;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AdminRequestSeeder extends Seeder
{
    public function run(): void
    {
        $requests = [
            [
                'requester_id' => 2,
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
                'requester_id' => 3,
                'target_type' => 'session',
                'action' => 'create',
                'target_id' => null,
                'payload' => json_encode([
                    'program_id' => 3,
                    'title' => 'Data Science with Python - Summer 2025',
                    'start_date' => '2025-06-01',
                    'end_date' => '2025-06-20',
                    'capacity' => 30,
                ]),
                'status' => 'approved',
                'admin_note' => 'Approved for summer session.',
                'resolved_by' => 1,
                'resolved_at' => Carbon::now()->subDays(2),
            ],
            [
                'requester_id' => 4,
                'target_type' => 'program',
                'action' => 'update',
                'target_id' => 4,
                'payload' => json_encode([
                    'duration_hours' => 60,
                    'description' => 'Extended cybersecurity course with advanced topics.',
                ]),
                'status' => 'rejected',
                'admin_note' => 'Duration change not approved. Please submit a new program instead.',
                'resolved_by' => 1,
                'resolved_at' => Carbon::now()->subDays(5),
            ],
            [
                'requester_id' => 2,
                'target_type' => 'trainee',
                'action' => 'add',
                'target_id' => 1,
                'payload' => json_encode([
                    'session_id' => 1,
                    'student_id' => 10,
                    'reason' => 'Late enrollment request from student.',
                ]),
                'status' => 'pending',
                'admin_note' => null,
                'resolved_by' => null,
                'resolved_at' => null,
            ],
            [
                'requester_id' => 3,
                'target_type' => 'session',
                'action' => 'update',
                'target_id' => 5,
                'payload' => json_encode([
                    'capacity' => 28,
                    'location' => 'Updated Cloud Lab, Building E',
                ]),
                'status' => 'approved',
                'admin_note' => 'Capacity increase and location change approved.',
                'resolved_by' => 1,
                'resolved_at' => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($requests as $request) {
            AdminRequest::create($request);
        }
    }
}
