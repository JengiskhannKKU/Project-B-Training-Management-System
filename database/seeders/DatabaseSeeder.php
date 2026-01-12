<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;


    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Roles and Users (with comprehensive profiles)
            AuthLoginSeeder::class,
            UserCategorySeeder::class,
            ProfileChangeSeeder::class,

            // 2. Courses and Sessions
            CourseSeeder::class,
            TrainingSessionSeeder::class,
            SessionDaySeeder::class, // NEW: Multi-day session support

            // 3. Enrollments
            EnrollmentSeeder::class,

            // 4. Attendance (now linked to session days)
            AttendanceSeeder::class,

            // 5. Reviews
            ReviewSeeder::class, // NEW: Session reviews

            // 6. Certificates
            CertificateTemplateSeeder::class,
            CertificateSeeder::class,
            CertificateRequestSeeder::class,

            // 7. Admin Requests
            AdminRequestSeeder::class,
        ]);

        $this->command->info('Database seeded successfully with comprehensive, synchronized test data.');
    }
}
