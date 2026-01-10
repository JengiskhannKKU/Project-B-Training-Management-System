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
        Schema::table('profiles', function (Blueprint $table) {
            // Personal
            $table->string('prefix')->nullable()->after('user_id');
            $table->string('first_name')->nullable()->after('prefix');
            $table->string('last_name')->nullable()->after('first_name');

            // Internal - Student
            $table->string('sub_category')->nullable()->after('gender'); // Student or Personnel
            $table->string('faculty')->nullable()->after('sub_category');
            $table->string('major')->nullable()->after('faculty');
            $table->string('student_id')->nullable()->after('major');
            $table->string('degree_level')->nullable()->after('student_id');
            $table->string('year_of_study')->nullable()->after('degree_level');

            // Internal - Personnel (organization and department already exist)
            $table->string('personnel_id')->nullable()->after('year_of_study');
            $table->string('job_position')->nullable()->after('personnel_id');
            $table->string('employment_status')->nullable()->after('job_position');
            $table->string('personnel_type')->nullable()->after('employment_status');

            // External
            $table->string('category')->nullable()->after('personnel_type'); // Student, Personnel, Outsider, Other
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'prefix',
                'first_name',
                'last_name',
                'sub_category',
                'faculty',
                'major',
                'student_id',
                'degree_level',
                'year_of_study',
                'personnel_id',
                'job_position',
                'employment_status',
                'personnel_type',
                'category',
            ]);
        });
    }
};
