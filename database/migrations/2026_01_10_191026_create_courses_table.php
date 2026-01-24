<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite: Temporarily disable foreign key constraints
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        // Drop old table if exists
        Schema::dropIfExists('programs');

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 100);
            $table->string('level')->nullable();
            $table->text('learning_outcomes')->nullable();
            $table->text('target_audience')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->integer('min_participants')->default(1);
            $table->integer('max_participants')->default(20);

            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
