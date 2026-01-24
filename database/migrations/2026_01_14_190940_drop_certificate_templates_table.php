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
        // Drop certificate_templates table as it's no longer needed with fixed template system
        Schema::dropIfExists('certificate_templates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We don't recreate the table in down() as the old template system is deprecated
        // If needed, restore from backup or earlier migration
    }
};
