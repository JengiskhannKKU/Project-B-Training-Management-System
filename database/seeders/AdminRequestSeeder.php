<?php

namespace Database\Seeders;

use App\Models\AdminRequest;
use App\Models\Course;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trainer = User::where('email', 'trainer@example.com')->first();
        $admin = User::where('email', 'admin@example.com')->first();
        $course = Course::first();
        $session = TrainingSession::first();

        if (!$trainer) {
            return;
        }

        // 1. Pending Course Creation Request
        AdminRequest::create([
            'requester_id' => $trainer->id,
            'target_type' => 'course',
            'action' => 'create',
            'target_id' => null, // New course
            'payload' => [
                'title' => 'New Angular Course',
                'description' => 'Comprehensive Angular guide.',
                'category' => 'Programming',
                'level' => 'intermediate',
                'min_participants' => 10,
                'max_participants' => 30,
            ],
            'status' => 'pending',
            'admin_note' => null,
        ]);

        // 2. Pending Session Creation Request
        AdminRequest::create([
            'requester_id' => $trainer->id,
            'target_type' => 'session',
            'action' => 'create',
            'target_id' => null,
            'payload' => [
                'course_id' => $course?->id ?? 1,
                'title' => 'Angular Batch 1',
                'start_date' => now()->addMonth()->toDateString(),
                'end_date' => now()->addMonth()->addDays(5)->toDateString(),
                'capacity' => 20,
            ],
            'status' => 'pending',
        ]);

        // 3. Rejected Course Update Request
        AdminRequest::create([
            'requester_id' => $trainer->id,
            'target_type' => 'course',
            'action' => 'update',
            'target_id' => $course?->id,
            'payload' => [
                'title' => 'Updated Web Dev',
                'min_participants' => 50, // Unreasonable change
            ],
            'status' => 'rejected',
            'admin_note' => 'Capacity change not approved. Please submit a new course instead.',
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);
    }
}