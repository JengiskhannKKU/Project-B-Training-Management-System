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
                'description' => 'Comprehensive Angular guide covering components, services, and routing.',
                'category' => 'Programming',
                'level' => 'intermediate',
                'learning_outcomes' => '• Master Angular Components\n• Services and Dependency Injection\n• Routing and Navigation',
                'target_audience' => 'Web developers familiar with JavaScript.',
                'prerequisites' => 'Basic JavaScript and TypeScript knowledge.',
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
                'start_at' => now()->addMonth()->toDateTimeString(),
                'end_at' => now()->addMonth()->addDays(5)->toDateTimeString(),
                'min_participants' => 10,
                'capacity' => 25,
                'mode' => 'online',
                'location' => 'Online',
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
                'title' => 'Updated Web Dev - Advanced Edition',
                'description' => 'Major update to include modern frameworks.',
            ],
            'status' => 'rejected',
            'admin_note' => 'Please create a new course instead of significantly modifying existing content.',
            'resolved_by' => $admin?->id,
            'resolved_at' => now(),
        ]);

        // 4. Approved Session Creation Request
        AdminRequest::create([
            'requester_id' => $trainer->id,
            'target_type' => 'session',
            'action' => 'create',
            'target_id' => null,
            'payload' => [
                'course_id' => $course?->id ?? 1,
                'title' => 'Web Dev Intensive Bootcamp',
                'start_at' => now()->addDays(60)->toDateTimeString(),
                'end_at' => now()->addDays(67)->toDateTimeString(),
                'min_participants' => 5,
                'capacity' => 15,
                'mode' => 'onsite',
                'location' => 'Training Center B',
            ],
            'status' => 'approved',
            'admin_note' => 'Approved. Please ensure proper room reservation.',
            'resolved_by' => $admin?->id,
            'resolved_at' => now()->subDays(5),
        ]);

        $this->command->info('Admin requests seeded: 4');
    }
}
