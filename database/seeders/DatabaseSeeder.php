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
            AuthLoginSeeder::class,
            ProgramSeeder::class,
            TrainingSessionSeeder::class,
            EnrollmentSeeder::class,
            AttendanceSeeder::class,
            CertificateTemplateSeeder::class,
            CertificateSeeder::class,
            CertificateRequestSeeder::class,
            AdminRequestSeeder::class,
        ]);

        $this->command->info('Database seeded successfully with comprehensive test data.');
    }
}
