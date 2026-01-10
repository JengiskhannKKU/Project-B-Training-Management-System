<?php

namespace Database\Seeders;

use App\Models\CertificateRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class CertificateRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trainer = User::where('email', 'trainer@example.com')->first();
        $admin = User::where('email', 'admin@example.com')->first();
        $course = Course::first();
        $session = TrainingSession::where('status', 'completed')->first();

        if (!$trainer || !$course) {
            return;
        }

        // 1. Pending Session Request
        if ($session) {
            CertificateRequest::create([
                'trainer_id' => $trainer->id,
                'session_id' => $session->id,
                'course_id' => $session->course_id,
                'type' => 'session',
                'status' => 'pending',
                'note' => 'Please approve certificates for the completed Web Dev session.',
            ]);
        }

        // 2. Pending Course Request
        CertificateRequest::create([
            'trainer_id' => $trainer->id,
            'course_id' => $course->id,
            'session_id' => null,
            'type' => 'course',
            'status' => 'pending',
            'note' => 'Requesting course-level certificates.',
        ]);

        // 3. Approved Request
        CertificateRequest::create([
            'trainer_id' => $trainer->id,
            'course_id' => $course->id,
            'session_id' => null,
            'type' => 'course',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'note' => 'Approved as requested.',
        ]);
    }
}