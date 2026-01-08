<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MockupDataSeeder extends Seeder
{
    /**
     * Seed mock data excluding auth login accounts.
     */
    public function run(): void
    {
        $this->call([
            ProgramSeeder::class,
            TrainingSessionSeeder::class,
            EnrollmentSeeder::class,
            AttendanceSeeder::class,
            CertificateTemplateSeeder::class,
            CertificateSeeder::class,
            CertificateRequestSeeder::class,
            AdminRequestSeeder::class,
        ]);

        $this->command?->info('Mockup data seeded (auth login preserved).');
    }
}
