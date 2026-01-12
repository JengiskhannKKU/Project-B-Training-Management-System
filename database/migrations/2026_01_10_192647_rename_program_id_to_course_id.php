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
        // 1. Training Sessions
        Schema::table('training_sessions', function (Blueprint $table) {
            // Drop foreign key if exists (might be named training_sessions_program_id_foreign)
            // Note: In SQLite, dropping FKs is tricky, but let's try standard Laravel way first.
            // If the programs table was dropped, the constraint might be dangling.
            
            // It's safer to rename column first, then add new FK.
            // But we need to check if we can drop the old FK first.
            $table->dropForeign(['program_id']); 
            $table->renameColumn('program_id', 'course_id');
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        // 2. Certificates
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->renameColumn('program_id', 'course_id');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        // 3. Session Reviews - Skip: already created with course_id in 2026_01_09_000000_create_session_reviews_table

        // 4. Certificate Templates
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->renameColumn('program_id', 'course_id');
        });

        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We won't strictly reverse this because 'programs' table is gone.
        // But for completeness, we'd rename back.
        
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->renameColumn('course_id', 'program_id');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->renameColumn('course_id', 'program_id');
        });

        // Skip session_reviews - it was created with course_id from the start

        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->renameColumn('course_id', 'program_id');
        });
    }
};
