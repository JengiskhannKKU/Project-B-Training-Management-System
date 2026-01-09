<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For SQLite, we need to handle this differently
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support dropping columns with foreign keys easily
            // We need to recreate the tables
            $this->recreateProgramsTable();
            $this->recreateTrainingSessionsTable();
        } else {
            // For MySQL/PostgreSQL, drop foreign keys first, then columns
            Schema::table('programs', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approval_status', 'approved_by', 'approved_at', 'approval_note']);
            });

            Schema::table('training_sessions', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approval_status', 'approved_by', 'approved_at', 'approval_note']);
            });
        }
    }

    private function recreateProgramsTable(): void
    {
        // Create temporary table with new structure
        Schema::create('programs_temp', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->string('category', 100);
            $table->string('level')->nullable();
            $table->integer('duration_hours');
            $table->string('image_url')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->timestamps();
        });

        // Copy data
        DB::statement('INSERT INTO programs_temp (id, name, code, description, category, level, duration_hours, image_url, created_by, status, created_at, updated_at) 
                       SELECT id, name, code, description, category, level, duration_hours, image_url, created_by, status, created_at, updated_at 
                       FROM programs');

        // Drop old table and rename temp
        Schema::dropIfExists('programs');
        Schema::rename('programs_temp', 'programs');
    }

    private function recreateTrainingSessionsTable(): void
    {
        // Create temporary table with new structure
        Schema::create('training_sessions_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('capacity');
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->string('trainer_name')->nullable();
            $table->string('trainer_photo_url')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['upcoming', 'open', 'closed', 'completed', 'cancelled'])->default('upcoming');
            $table->timestamps();
        });

        // Copy data
        DB::statement('INSERT INTO training_sessions_temp (id, program_id, title, start_date, end_date, start_time, end_time, capacity, trainer_id, trainer_name, trainer_photo_url, location, status, created_at, updated_at) 
                       SELECT id, program_id, title, start_date, end_date, start_time, end_time, capacity, trainer_id, trainer_name, trainer_photo_url, location, status, created_at, updated_at 
                       FROM training_sessions');

        // Drop old table and rename temp
        Schema::dropIfExists('training_sessions');
        Schema::rename('training_sessions_temp', 'training_sessions');
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
        });
    }
};
