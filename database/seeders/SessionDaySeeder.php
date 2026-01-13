<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SessionDaySeeder extends Seeder
{
    /**
     * Seed session days for all training sessions.
     *
     * NOTE: This seeder is now obsolete. Session days are created directly
     * by TrainingSessionSeeder. This is kept for backwards compatibility
     * but does nothing.
     */
    public function run(): void
    {
        $this->command->info('SessionDaySeeder is now obsolete. Session days are created by TrainingSessionSeeder.');
    }
}
