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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('session_id')->constrained('training_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Rating Fields (1-5)
            $table->unsignedTinyInteger('overall_rating');
            $table->unsignedTinyInteger('content_quality');
            $table->unsignedTinyInteger('trainer_quality');
            $table->unsignedTinyInteger('material_quality');
            $table->unsignedTinyInteger('organization');

            // Additional Fields
            $table->boolean('would_recommend');
            $table->enum('difficulty_level', ['easy', 'medium', 'hard']);

            // Text Feedback
            $table->text('strengths');
            $table->text('improvements');
            $table->text('comments')->nullable();

            // Submission Timestamp
            $table->dateTime('submitted_at');

            $table->timestamps();

            // Unique Constraint: One evaluation per user per session
            $table->unique(['session_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
