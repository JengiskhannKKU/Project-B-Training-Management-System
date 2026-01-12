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
        Schema::table('profiles', function (Blueprint $table) {
            // Add missing RBAC fields
            if (!Schema::hasColumn('profiles', 'address')) {
                $table->text('address')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('profiles', 'education_level')) {
                $table->string('education_level')->nullable()->after('address');
            }
            if (!Schema::hasColumn('profiles', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('education_level');
            }
            if (!Schema::hasColumn('profiles', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('profiles', 'profile_picture_path')) {
                $table->string('profile_picture_path')->nullable()->after('avatar_mime_type')->comment('Path to profile picture file');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $columnsToDrop = [];
            $candidates = ['address', 'education_level', 'emergency_contact_name', 'emergency_contact_phone', 'profile_picture_path'];

            foreach ($candidates as $col) {
                if (Schema::hasColumn('profiles', $col)) {
                    $columnsToDrop[] = $col;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
