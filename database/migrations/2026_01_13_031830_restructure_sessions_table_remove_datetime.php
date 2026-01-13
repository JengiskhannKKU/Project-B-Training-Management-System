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
        Schema::table('training_sessions', function (Blueprint $table) {
            // Remove start_at and end_at columns as dates will be managed through session_days
            $table->dropColumn(['start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            // Add back start_at and end_at columns for rollback
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
        });
    }
};
