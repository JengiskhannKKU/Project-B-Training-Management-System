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
        // Drop existing certificates table to recreate with consolidated schema
        Schema::dropIfExists('certificates');

        // Create certificates table with all fields consolidated
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('training_sessions')->nullOnDelete();

            // Issuer and revocation
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();

            // Certificate identification
            $table->string('certificate_code', 100)->unique();
            $table->enum('status', ['valid', 'revoked'])->default('valid');

            // Certificate content (English A4 Landscape)
            $table->string('language', 5)->default('en');
            $table->text('description')->nullable();
            $table->integer('total_hours')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('skills')->nullable();

            // Organization details
            $table->string('organization_name')->nullable();
            $table->string('organization_logo_url')->nullable();

            // Trainer information
            $table->json('trainer_ids')->nullable();
            $table->json('trainer_signatures')->nullable();

            // Authorized signatory
            $table->string('authorized_signatory_name')->nullable();
            $table->string('authorized_signature_url')->nullable();

            // QR code and verification
            $table->string('qr_code_url')->nullable();

            // File storage (PDF)
            $table->string('file_url')->nullable();
            $table->binary('file_data')->nullable();
            $table->string('file_mime_type', 100)->default('application/pdf');
            $table->unsignedInteger('file_size')->nullable();
            $table->dateTime('generated_at')->nullable();

            // Revocation note
            $table->text('revoked_note')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->unique('enrollment_id');
            $table->index(['course_id', 'user_id', 'status'], 'idx_cert_course_user_status');
            $table->index('certificate_code', 'idx_cert_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
