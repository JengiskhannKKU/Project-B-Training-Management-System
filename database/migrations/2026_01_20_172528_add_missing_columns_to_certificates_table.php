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
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'language')) {
                $table->string('language', 5)->default('en')->after('status');
            }
            if (!Schema::hasColumn('certificates', 'description')) {
                $table->text('description')->nullable()->after('language');
            }
            if (!Schema::hasColumn('certificates', 'total_hours')) {
                $table->integer('total_hours')->nullable()->after('description');
            }
            if (!Schema::hasColumn('certificates', 'score')) {
                $table->decimal('score', 5, 2)->nullable()->after('total_hours');
            }
            if (!Schema::hasColumn('certificates', 'skills')) {
                $table->text('skills')->nullable()->after('score');
            }
            if (!Schema::hasColumn('certificates', 'trainer_ids')) {
                $table->json('trainer_ids')->nullable()->after('skills');
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
                $table->string('organization_name')->nullable()->after('authorized_signature_url');
            }
            if (!Schema::hasColumn('certificates', 'organization_logo_url')) {
                $table->string('organization_logo_url')->nullable()->after('organization_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $columns = [
                'language', 'description', 'total_hours', 'score', 'skills',
                'trainer_ids', 'trainer_signatures', 'authorized_signatory_name',
                'authorized_signature_url', 'organization_name', 'organization_logo_url'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('certificates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
