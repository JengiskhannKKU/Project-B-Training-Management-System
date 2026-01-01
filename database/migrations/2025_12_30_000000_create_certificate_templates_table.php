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
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('scope', ['program', 'session', 'global']);
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('training_sessions')->nullOnDelete();
            $table->binary('background_image')->nullable();
            $table->string('background_mime_type', 100)->nullable();
            $table->json('layout_config')->nullable();
            $table->string('font_family')->nullable();
            $table->unsignedInteger('font_size')->nullable();
            $table->string('text_color', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
