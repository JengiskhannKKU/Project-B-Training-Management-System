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
            $table->dateTime('registration_start')->nullable()->after('max_participants');
            $table->dateTime('registration_end')->nullable()->after('registration_start');
            $table->enum('mode', ['onsite', 'online', 'hybrid'])->default('onsite')->after('location');
            $table->string('online_link')->nullable()->after('mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn(['registration_start', 'registration_end', 'mode', 'online_link']);
        });
    }
};