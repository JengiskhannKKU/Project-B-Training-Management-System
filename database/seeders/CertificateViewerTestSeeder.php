<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Role;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CertificateViewerTestSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create roles
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $trainerRole = Role::firstOrCreate(['name' => 'trainer']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Create test users
        $student = User::firstOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Student User',
                'password' => bcrypt('password'),
                'role_id' => $studentRole->id,
            ]
        );

        $trainer = User::firstOrCreate(
            ['email' => 'trainer@example.com'],
            [
                'name' => 'Trainer User',
                'password' => bcrypt('password'),
                'role_id' => $trainerRole->id,
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role_id' => $adminRole->id,
            ]
        );

        // Create test program
        $program = Program::firstOrCreate(
            ['name' => 'Full Stack Web Development'],
            [
                'description' => 'Learn modern web development with React, Node.js, and databases.',
                'category' => 'Programming',
                'duration_hours' => 120,
                'level' => 'intermediate',
                'created_by' => $trainer->id,
                'status' => 'approved',
                'approved_at' => Carbon::now()->subDays(30),
                'approved_by' => $admin->id,
            ]
        );

        // Create completed session
        $session = TrainingSession::firstOrCreate(
            [
                'program_id' => $program->id,
                'title' => 'Full Stack Web Development - Batch 1',
            ],
            [
                'trainer_id' => $trainer->id,
                'start_date' => Carbon::now()->subDays(60),
                'end_date' => Carbon::now()->subDays(30),
                'location' => 'Online via Zoom',
                'capacity' => 30,
                'status' => 'completed',
            ]
        );

        // Create enrollment for student
        $enrollment = Enrollment::firstOrCreate(
            [
                'user_id' => $student->id,
                'session_id' => $session->id,
            ],
            [
                'status' => 'completed',
                'enrolled_at' => Carbon::now()->subDays(60),
                'completed_at' => Carbon::now()->subDays(30),
            ]
        );

        // Create certificates for the student
        Certificate::firstOrCreate(
            [
                'user_id' => $student->id,
                'enrollment_id' => $enrollment->id,
            ],
            [
                'program_id' => $program->id,
                'session_id' => $session->id,
                'certificate_code' => 'CERT-' . strtoupper(uniqid()),
                'issued_at' => Carbon::now()->subDays(25),
                'issued_by' => $trainer->id,
                'status' => 'valid',
            ]
        );

        $this->command->info('Certificate test data created successfully!');
        $this->command->info('Student: student@example.com / password');
        $this->command->info('Trainer: trainer@example.com / password');
        $this->command->info('Admin: admin@example.com / password');
    }
}
