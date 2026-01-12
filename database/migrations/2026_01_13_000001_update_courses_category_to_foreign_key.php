<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Category;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, ensure we have categories in the database
        $this->seedCategoriesIfNeeded();

        // Add the new category_id column
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('description')->constrained()->cascadeOnDelete();
        });

        // Migrate existing category strings to category IDs
        $this->migrateCategoryData();

        // Make category_id required and drop the old category column
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the category string column
        Schema::table('courses', function (Blueprint $table) {
            $table->string('category', 100)->after('description');
        });

        // Migrate category IDs back to strings
        $courses = DB::table('courses')->get();
        foreach ($courses as $course) {
            if ($course->category_id) {
                $category = Category::find($course->category_id);
                if ($category) {
                    DB::table('courses')
                        ->where('id', $course->id)
                        ->update(['category' => $category->name]);
                }
            }
        }

        // Drop the category_id column
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    /**
     * Seed categories if they don't exist
     */
    private function seedCategoriesIfNeeded(): void
    {
        $existingCount = Category::count();

        if ($existingCount === 0) {
            $categories = [
                ['name' => 'IT', 'icon_name' => 'laptop', 'color' => '#3b82f6'],
                ['name' => 'Management', 'icon_name' => 'briefcase', 'color' => '#8b5cf6'],
                ['name' => 'Design', 'icon_name' => 'palette', 'color' => '#ec4899'],
                ['name' => 'Marketing', 'icon_name' => 'megaphone', 'color' => '#f59e0b'],
                ['name' => 'Business', 'icon_name' => 'trending-up', 'color' => '#10b981'],
                ['name' => 'Professional Development', 'icon_name' => 'graduation-cap', 'color' => '#2f837d'],
            ];

            foreach ($categories as $category) {
                Category::create($category);
            }
        }
    }

    /**
     * Migrate existing category strings to category IDs
     */
    private function migrateCategoryData(): void
    {
        $courses = DB::table('courses')->get();

        foreach ($courses as $course) {
            if (!empty($course->category)) {
                $category = Category::where('name', $course->category)->first();

                if (!$category) {
                    // Create a new category if it doesn't exist
                    $category = Category::create([
                        'name' => $course->category,
                        'icon_name' => 'folder',
                        'color' => '#6b7280', // Gray default
                    ]);
                }

                DB::table('courses')
                    ->where('id', $course->id)
                    ->update(['category_id' => $category->id]);
            }
        }
    }
};
