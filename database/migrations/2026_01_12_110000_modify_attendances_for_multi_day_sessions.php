<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop the old unique constraint on enrollment_id
            $table->dropUnique(['enrollment_id']);

            // Add new columns for multi-day attendance tracking
            $table->foreignId('session_day_id')
                ->nullable()
                ->after('session_id')
                ->constrained('session_days')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->after('enrollment_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Create new composite unique constraint: one attendance per user per day
            $table->unique(['session_day_id', 'user_id'], 'attendance_day_user_unique');

            // Add index for query optimization
            $table->index(['enrollment_id', 'session_day_id'], 'attendance_enrollment_day_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop the indexes and foreign keys
            $table->dropIndex('attendance_enrollment_day_index');
            $table->dropUnique('attendance_day_user_unique');
            $table->dropForeign(['session_day_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['session_day_id', 'user_id']);

            // Restore the original unique constraint on enrollment_id
            $table->unique('enrollment_id');
        });
    }
};
