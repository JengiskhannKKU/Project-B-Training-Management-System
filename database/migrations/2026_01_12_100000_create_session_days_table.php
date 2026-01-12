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
        Schema::create('session_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('day_number')->comment('Sequential day number: 1, 2, 3, etc.');
            $table->timestamps();

            // Prevent duplicate dates per session
            $table->unique(['session_id', 'date']);

            // Query optimization for ordering by day
            $table->index(['session_id', 'day_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_days');
    }
};
