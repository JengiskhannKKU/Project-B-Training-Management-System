<?php


namespace Database\Seeders;

use App\Models\TrainingSession;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TrainingSessionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstWhere('email', 'admin@example.com');
        $approver = $admin ?? User::whereHas('role', function ($q) {
            $q->where('name', 'admin'); })->first() ?? User::first();

        $trainer = User::firstWhere('email', 'trainer@example.com');
        $sarah = User::firstWhere('email', 'sarah.trainer@example.com');
        $mike = User::firstWhere('email', 'mike.trainer@example.com');
        $david = User::firstWhere('email', 'david.trainer@example.com');

        $trainers = [
            'default' => $trainer,
            'sarah' => $sarah,
            'mike' => $mike,
            'david' => $david,
        ];

        // Helper to get program ID by code
        $getProgramId = function ($code) {
            return Program::where('code', $code)->value('id');
        };

        $sessions = [
            // Active / Upcoming
            [
                'program_id' => $getProgramId('WEB-101'),
                'title' => 'Web Development Fundamentals - Spring 2025',
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addDays(20),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'capacity' => 25,
                'trainer_id' => $trainer->id ?? 2,
                'trainer_name' => $trainer->name ?? 'John Trainer',
                'trainer_photo_url' => 'https://via.placeholder.com/150',
                'location' => 'Room 101, Main Building',
                'status' => 'open', // Open for enrollment
                'approval_status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subDays(5),
                'approval_note' => 'Approved for spring session.',
            ],
            [
                'program_id' => $getProgramId('REACT-201'),
                'title' => 'Advanced React Development - Q1 2025',
                'start_date' => Carbon::now()->addDays(15),
                'end_date' => Carbon::now()->addDays(30),
                'start_time' => '10:00:00',
                'end_time' => '16:00:00',
                'capacity' => 20,
                'trainer_id' => $trainer->id ?? 2,
                'trainer_name' => $trainer->name ?? 'John Trainer',
                'trainer_photo_url' => 'https://via.placeholder.com/150',
                'location' => 'Room 202, Tech Center',
                'status' => 'open',
                'approval_status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subDays(4),
                'approval_note' => 'Ready to proceed.',
            ],

            // Completed (Past)
            [
                'program_id' => $getProgramId('DS-101'),
                'title' => 'Data Science with Python - Winter 2025',
                'start_date' => Carbon::now()->subDays(30),
                'end_date' => Carbon::now()->subDays(10),
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'capacity' => 30,
                'trainer_id' => $sarah->id ?? 3,
                'trainer_name' => $sarah->name ?? 'Sarah Trainer',
                'trainer_photo_url' => 'https://via.placeholder.com/150',
                'location' => 'Lab 301, Science Building',
                'status' => 'completed',
                'approval_status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subDays(35),
                'approval_note' => 'Great session planned.',
            ],

            // Upcoming
            [
                'program_id' => $getProgramId('SEC-101'),
                'title' => 'Cybersecurity Essentials - February 2025',
                'start_date' => Carbon::now()->addDays(20),
                'end_date' => Carbon::now()->addDays(35),
                'start_time' => '13:00:00',
                'end_time' => '18:00:00',
                'capacity' => 15,
                'trainer_id' => $mike->id ?? 4,
                'trainer_name' => $mike->name ?? 'Mike Trainer',
                'trainer_photo_url' => 'https://via.placeholder.com/150',
                'location' => 'Security Lab, Building C',
                'status' => 'upcoming',
                'approval_status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subDays(3),
                'approval_note' => 'Approved.',
            ],

            // Pending Approval
            [
                'program_id' => $getProgramId('CLOUD-101'),
                'title' => 'Cloud Computing with AWS - March 2025',
                'start_date' => Carbon::now()->addDays(40),
                'end_date' => Carbon::now()->addDays(60),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'capacity' => 22,
                'trainer_id' => $david->id ?? 2,
                'trainer_name' => $david->name ?? 'David Cloud',
                'trainer_photo_url' => 'https://via.placeholder.com/150',
                'location' => 'Cloud Lab, Building D',
                'status' => 'upcoming',
                'approval_status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'approval_note' => null,
            ],

            // Completed (Old)
            [
                'program_id' => $getProgramId('WEB-101'),
                'title' => 'Web Development Fundamentals - Winter 2024',
                'start_date' => Carbon::now()->subDays(60),
                'end_date' => Carbon::now()->subDays(50),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'capacity' => 25,
                'trainer_id' => $trainer->id ?? 2,
                'trainer_name' => $trainer->name ?? 'John Trainer',
                'trainer_photo_url' => 'https://via.placeholder.com/150',
                'location' => 'Room 101, Main Building',
                'status' => 'completed',
                'approval_status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subDays(65),
                'approval_note' => 'Approved.',
            ],

            // Upcoming
            [
                'program_id' => $getProgramId('DEVOPS-101'),
                'title' => 'DevOps and CI/CD - April 2025',
                'start_date' => Carbon::now()->addDays(50),
                'end_date' => Carbon::now()->addDays(65),
                'start_time' => '10:00:00',
                'end_time' => '16:00:00',
                'capacity' => 18,
                'trainer_id' => $mike->id ?? 4,
                'trainer_name' => $mike->name ?? 'Mike Trainer',
                'trainer_photo_url' => 'https://via.placeholder.com/150',
                'location' => 'DevOps Lab, Tech Center',
                'status' => 'upcoming',
                'approval_status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subDays(2),
                'approval_note' => 'Looking forward to this session.',
            ],

            // Cancelled
            [
                'program_id' => $getProgramId('REACT-201'),
                'title' => 'Advanced React Development - Cancelled Session',
                'start_date' => Carbon::now()->addDays(5),
                'end_date' => Carbon::now()->addDays(15),
                'start_time' => '10:00:00',
                'end_time' => '16:00:00',
                'capacity' => 20,
                'trainer_id' => $trainer->id ?? 2,
                'trainer_name' => $trainer->name ?? 'John Trainer',
                'trainer_photo_url' => 'https://via.placeholder.com/150',
                'location' => 'Room 202, Tech Center',
                'status' => 'cancelled',
                'approval_status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subDays(10),
                'approval_note' => 'Initially approved but later cancelled due to low enrollment.',
            ],
        ];

        foreach ($sessions as $session) {
            // Only create if program exists
            if ($session['program_id']) {
                TrainingSession::updateOrCreate(
                    ['title' => $session['title']],
                    $session
                );
            }
        }
    }
}

