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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropUnique(['student_id', 'session_id']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->renameColumn('student_id', 'user_id');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'session_id']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->renameColumn('student_id', 'user_id');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'session_id']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->renameColumn('user_id', 'student_id');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['student_id', 'session_id']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->renameColumn('user_id', 'student_id');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
