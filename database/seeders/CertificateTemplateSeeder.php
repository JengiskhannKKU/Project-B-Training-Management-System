<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\TrainingSession;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Global Template (Default)
        CertificateTemplate::create([
            'name' => 'Default Global Template',
            'scope' => 'global',
            'course_id' => null,
            'session_id' => null,
            'layout_config' => [
                'canvas' => ['width' => 2000, 'height' => 1414],
                'name' => [
                    'x' => 1000,
                    'y' => 600,
                    'fontSize' => 60,
                    'font' => 'Prompt-Bold.ttf',
                    'color' => '#1f2937',
                    'align' => 'center',
                ],
                'course' => [
                    'x' => 1000,
                    'y' => 800,
                    'fontSize' => 40,
                    'font' => 'Prompt-Regular.ttf',
                    'color' => '#4b5563',
                    'align' => 'center',
                ],
                'date' => [
                    'x' => 1000,
                    'y' => 1000,
                    'fontSize' => 30,
                    'font' => 'Prompt-Regular.ttf',
                    'color' => '#6b7280',
                    'align' => 'center',
                    'format' => 'F j, Y',
                ],
                'certificate_code' => [
                    'x' => 1000,
                    'y' => 1200,
                    'fontSize' => 20,
                    'font' => 'Courier',
                    'color' => '#9ca3af',
                    'align' => 'center',
                ],
                'qr' => [
                    'x' => 1700,
                    'y' => 1100,
                    'size' => 200,
                ],
            ],
            'font_family' => 'Prompt-Regular.ttf',
            'font_size' => 40,
            'text_color' => '#000000',
            'is_active' => true,
        ]);

        // 2. Course Specific Template
        $webCourse = Course::where('title', 'Web Development Fundamentals')->first();
        if ($webCourse) {
            CertificateTemplate::create([
                'name' => 'Web Dev Special Template',
                'scope' => 'course',
                'course_id' => $webCourse->id,
                'session_id' => null,
                'layout_config' => [
                    'canvas' => ['width' => 2000, 'height' => 1414],
                    'name' => ['x' => 1000, 'y' => 500, 'fontSize' => 70, 'color' => '#2563eb'],
                    'course' => ['x' => 1000, 'y' => 700, 'fontSize' => 50],
                ],
                'is_active' => true,
            ]);
        }

        // 3. Session Specific Template
        $session = TrainingSession::first();
        if ($session) {
            CertificateTemplate::create([
                'name' => 'Session Exclusive Template',
                'scope' => 'session',
                'course_id' => null,
                'session_id' => $session->id,
                'layout_config' => [
                    'canvas' => ['width' => 2000, 'height' => 1414],
                    'name' => ['x' => 1000, 'y' => 500, 'fontSize' => 70, 'color' => '#dc2626'],
                    'course' => ['x' => 1000, 'y' => 700, 'fontSize' => 50],
                ],
                'is_active' => true,
            ]);
        }
    }
}