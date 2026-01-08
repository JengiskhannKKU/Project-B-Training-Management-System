<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use App\Models\Program;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Global Standard Template
        CertificateTemplate::updateOrCreate(
            ['name' => 'Standard Global Certificate'],
            [
                'scope' => 'global',
                'program_id' => null,
                'session_id' => null,
                'layout_config' => [
                    'items' => [
                        [
                            'type' => 'text',
                            'key' => 'title',
                            'label' => 'Title',
                            'x' => 50,
                            'y' => 20,
                            'fontSize' => 32,
                            'text' => 'Certificate of Completion'
                        ],
                        [
                            'type' => 'placeholder',
                            'key' => 'student_name',
                            'label' => 'Student Name',
                            'x' => 50,
                            'y' => 45,
                            'fontSize' => 24
                        ],
                        [
                            'type' => 'text',
                            'key' => 'body',
                            'label' => 'Body Text',
                            'x' => 50,
                            'y' => 55,
                            'fontSize' => 16,
                            'text' => 'This is to certify that the above named individual has successfully completed the course.'
                        ],
                        [
                            'type' => 'placeholder',
                            'key' => 'course_name',
                            'label' => 'Course Name',
                            'x' => 50,
                            'y' => 65,
                            'fontSize' => 20
                        ],
                        [
                            'type' => 'placeholder',
                            'key' => 'issue_date',
                            'label' => 'Issue Date',
                            'x' => 20,
                            'y' => 80,
                            'fontSize' => 14
                        ]
                    ]
                ],
                'is_active' => true,
            ]
        );

        // 2. Program Specific Template
        $webProgram = Program::where('code', 'WEB-101')->first();
        if ($webProgram) {
            CertificateTemplate::updateOrCreate(
                ['name' => 'Web Development Certificate'],
                [
                    'scope' => 'program',
                    'program_id' => $webProgram->id,
                    'session_id' => null,
                    'layout_config' => [
                        'items' => [
                            [
                                'type' => 'text',
                                'text' => 'Web Development Specialist',
                                'x' => 50,
                                'y' => 30,
                                'fontSize' => 40,
                                'color' => '#4F46E5'
                            ]
                            // ... simple config
                        ]
                    ],
                    'is_active' => true,
                ]
            );
        }
    }
}
