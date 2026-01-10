<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class ClearDatabaseExceptAuthSeeder extends Seeder
{
    /**
     * Truncate application tables while keeping auth login data.
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
            'courses',
            'admin_requests',
            'auth_sessions',
            'personal_access_tokens',
            'password_reset_tokens',
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

        $this->clearSessionFiles();

        $this->command?->info('Database cleared (auth login preserved).');
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

    private function clearSessionFiles(): void
    {
        if (config('session.driver') !== 'file') {
            return;
        }

        $sessionPath = storage_path('framework/sessions');
        if (!is_dir($sessionPath)) {
            return;
        }

        foreach (File::files($sessionPath) as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }
            File::delete($file->getPathname());
        }
    }
}
