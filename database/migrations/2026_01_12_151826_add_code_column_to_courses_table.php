<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Course;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add code column (nullable initially)
        Schema::table('courses', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
        });

        // Generate codes for existing courses
        $courses = Course::whereNull('code')->get();
        foreach ($courses as $course) {
            $course->code = $this->generateUniqueCourseCode();
            $course->save();
        }

        // Make code column unique and non-nullable
        Schema::table('courses', function (Blueprint $table) {
            $table->string('code')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }

    /**
     * Generate a unique course code
     */
    private function generateUniqueCourseCode(): string
    {
        do {
            $code = 'CRS-' . Str::upper(Str::random(8));
        } while (Course::where('code', $code)->exists());

        return $code;
    }
};
