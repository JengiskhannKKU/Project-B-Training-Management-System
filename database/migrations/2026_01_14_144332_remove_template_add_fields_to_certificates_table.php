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
        // First, update existing NULL file_mime_type values
        \DB::table('certificates')
            ->whereNull('file_mime_type')
            ->update(['file_mime_type' => 'application/pdf']);

        Schema::table('certificates', function (Blueprint $table) {
            // Remove template relationship
            if (Schema::hasColumn('certificates', 'template_id')) {
                $table->dropForeign(['template_id']);
                $table->dropColumn('template_id');
            }

            // Add new fields for fixed template system (only if they don't exist)
            if (!Schema::hasColumn('certificates', 'description')) {
                $table->text('description')->nullable()->after('course_id');
            }
            if (!Schema::hasColumn('certificates', 'total_hours')) {
                $table->unsignedInteger('total_hours')->nullable()->after('description');
            }
            if (!Schema::hasColumn('certificates', 'trainer_ids')) {
                $table->json('trainer_ids')->nullable()->after('issued_by');
            }
            if (!Schema::hasColumn('certificates', 'trainer_signatures')) {
                $table->json('trainer_signatures')->nullable()->after('trainer_ids');
            }
            if (!Schema::hasColumn('certificates', 'authorized_signatory_name')) {
                $table->string('authorized_signatory_name')->nullable()->after('trainer_signatures');
            }
            if (!Schema::hasColumn('certificates', 'authorized_signature_url')) {
                $table->string('authorized_signature_url')->nullable()->after('authorized_signatory_name');
            }
            if (!Schema::hasColumn('certificates', 'organization_name')) {
                $table->string('organization_name')->default('KKU')->after('authorized_signature_url');
            }
            if (!Schema::hasColumn('certificates', 'organization_logo_url')) {
                $table->string('organization_logo_url')->nullable()->after('organization_name');
            }
            if (!Schema::hasColumn('certificates', 'language')) {
                $table->string('language', 2)->default('th')->after('organization_logo_url');
            }
            if (!Schema::hasColumn('certificates', 'score')) {
                $table->decimal('score', 5, 2)->nullable()->after('language');
            }
            if (!Schema::hasColumn('certificates', 'skills')) {
                $table->text('skills')->nullable()->after('score');
            }
            if (!Schema::hasColumn('certificates', 'qr_code_url')) {
                $table->string('qr_code_url')->nullable()->after('skills');
            }

            // Update existing field default
            if (Schema::hasColumn('certificates', 'file_mime_type')) {
                $table->string('file_mime_type')->default('application/pdf')->change();
            }
        });

        // Add performance indexes (wrapped in try-catch to handle duplicate index errors)
        try {
            Schema::table('certificates', function (Blueprint $table) {
                $table->index(['course_id', 'user_id', 'status'], 'idx_cert_course_user_status');
            });
        } catch (\Exception $e) {
            // Index might already exist
        }

        try {
            Schema::table('certificates', function (Blueprint $table) {
                $table->index('certificate_code', 'idx_cert_code');
            });
        } catch (\Exception $e) {
            // Index might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Restore template relationship (if table still exists)
            if (Schema::hasTable('certificate_templates')) {
                $table->foreignId('template_id')->nullable()->after('session_id')->constrained('certificate_templates')->nullOnDelete();
            }

            // Remove added fields
            $table->dropColumn([
                'description',
                'total_hours',
                'trainer_ids',
                'trainer_signatures',
                'authorized_signatory_name',
                'authorized_signature_url',
                'organization_name',
                'organization_logo_url',
                'language',
                'score',
                'skills',
                'qr_code_url',
            ]);

            // Drop indexes
            $table->dropIndex('idx_cert_course_user_status');
            $table->dropIndex('idx_cert_code');
        });
    }
};
