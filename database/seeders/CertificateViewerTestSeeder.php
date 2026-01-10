<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CertificateViewerTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have a user
        $user = User::firstOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Test Student',
                'password' => bcrypt('password'),
                'role_id' => 1, // Assuming 1 is student/trainee
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role_id' => 3, // Assuming 3 is admin
            ]
        );

        // Create test course
        $course = Course::firstOrCreate(
            ['title' => 'Certificate Test Course'],
            [
                'description' => 'A course to test certificate viewing',
                'category' => 'Programming',
                'level' => 'intermediate',
                'owner_id' => $admin->id,
                'status' => 'published',
            ]
        );

        // Create test session
        $session = TrainingSession::firstOrCreate(
            ['title' => 'Certificate Test Session'],
            [
                'course_id' => $course->id,
                'start_date' => now()->subDays(10),
                'end_date' => now()->subDays(5),
                'capacity' => 20,
                'trainer_id' => $admin->id,
                'status' => 'completed',
                'approval_status' => 'approved',
            ]
        );

        // Create enrollment
        $enrollment = Enrollment::firstOrCreate(
            [
                'user_id' => $user->id,
                'session_id' => $session->id,
            ],
            [
                'status' => 'completed',
                'completed_at' => now()->subDays(1),
            ]
        );

        // Create a template
        $template = CertificateTemplate::firstOrCreate(
            ['name' => 'Test Template'],
            [
                'scope' => 'global',
                'layout_config' => [
                    'canvas' => ['width' => 2000, 'height' => 1414],
                    'name' => ['x' => 1000, 'y' => 600, 'fontSize' => 60, 'align' => 'center'],
                    'course' => ['x' => 1000, 'y' => 800, 'fontSize' => 40, 'align' => 'center'],
                    'date' => ['x' => 1000, 'y' => 1000, 'fontSize' => 30, 'align' => 'center'],
                ],
                'is_active' => true,
            ]
        );

        // Create a certificate
        $code = 'TEST-VIEW-' . Str::upper(Str::random(6));
        
        if (!Certificate::where('certificate_code', $code)->exists()) {
            Certificate::create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'session_id' => $session->id,
                'template_id' => $template->id,
                'issued_by' => $admin->id,
                'issued_at' => now(),
                'certificate_code' => $code,
                'status' => 'valid',
                'file_data' => 'fake_pdf_content', // Mock data
                'file_mime_type' => 'application/pdf',
                'file_size' => 1024,
            ]);
        }
    }
}