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
            RoleSeeder::class,
            UserSeeder::class,
            ProfileSeeder::class,
            ProgramSeeder::class,
            TrainingSessionSeeder::class,
            EnrollmentSeeder::class,
            AttendanceSeeder::class,
            CertificateRequestSeeder::class,
            CertificateSeeder::class,
            AdminRequestSeeder::class,
        ]);

        $this->command->info('All database tables seeded successfully!');
    }
}
