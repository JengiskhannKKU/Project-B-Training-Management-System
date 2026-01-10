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
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->renameColumn('program_id', 'course_id');
        });

        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->renameColumn('course_id', 'program_id');
        });
    }
};
