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
        Schema::create('certificate_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('year')->unique();
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_sequences');
    }
};
