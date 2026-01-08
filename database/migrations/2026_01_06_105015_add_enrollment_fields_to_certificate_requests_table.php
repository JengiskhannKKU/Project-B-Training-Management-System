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
            // Add enrollment reference for individual student requests
            $table->foreignId('enrollment_id')->nullable()->after('session_id')
                  ->constrained('enrollments')->onDelete('cascade');

            // Add requester (student who requested)
            $table->foreignId('requested_by')->nullable()->after('trainer_id')
                  ->constrained('users')->onDelete('cascade');

            // Add indexes for performance
            $table->index(['enrollment_id', 'status']);
            $table->index(['requested_by', 'status']);
        });

        // Make trainer_id nullable (students can create requests too)
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->foreignId('trainer_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropIndex(['enrollment_id', 'status']);
            $table->dropIndex(['requested_by', 'status']);
            $table->dropForeign(['enrollment_id']);
            $table->dropForeign(['requested_by']);
            $table->dropColumn(['enrollment_id', 'requested_by']);
        });
    }
};
