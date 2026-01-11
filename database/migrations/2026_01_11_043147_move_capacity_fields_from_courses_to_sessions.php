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
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['min_participants', 'max_participants']);
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->integer('min_participants')->default(1)->after('capacity');
            $table->renameColumn('capacity', 'max_participants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->integer('min_participants')->default(1);
            $table->integer('max_participants')->default(20);
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn(['min_participants']);
            $table->renameColumn('max_participants', 'capacity');
        });
    }
};