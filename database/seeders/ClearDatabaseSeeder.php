<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearDatabaseSeeder extends Seeder
{
    /**
     * Truncate all application tables and reset IDs.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'attendances',
            'certificate_requests',
            'certificates',
            'certificate_templates',
            'enrollments',
            'session_reviews',
            'training_sessions',
            'programs',
            'admin_requests',
            'profiles',
            'auth_sessions',
            'personal_access_tokens',
            'password_reset_tokens',
            'users',
            'roles',
            'sessions',
            'jobs',
            'job_batches',
            'failed_jobs',
            'cache',
            'cache_locks',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $this->truncateTable($table);
        }

        Schema::enableForeignKeyConstraints();

        $this->command?->info('Database cleared successfully.');
    }

    private function truncateTable(string $table): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DELETE FROM {$table}");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = '{$table}'");
            return;
        }

        DB::table($table)->truncate();
    }
}
