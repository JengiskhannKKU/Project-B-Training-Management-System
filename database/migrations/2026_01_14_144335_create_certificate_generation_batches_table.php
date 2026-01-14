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
        Schema::create('certificate_generation_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiated_by')->constrained('users');
            $table->string('type'); // 'course' or 'session'
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('training_sessions')->onDelete('cascade');
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('generated_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_generation_batches');
    }
};
