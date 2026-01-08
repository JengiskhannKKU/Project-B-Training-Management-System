<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MockupSeeder extends Seeder
{
    /**
     * Reset the database and seed mock data for demos.
     */
    public function run(): void
    {
        $this->call([
            ClearDatabaseExceptAuthSeeder::class,
            MockupDataSeeder::class,
        ]);

        $this->command?->info('Mockup database seed complete (auth login preserved).');
    }
}
