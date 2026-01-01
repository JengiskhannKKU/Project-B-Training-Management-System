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
            $table->binary('file_data')->nullable()->after('file_url');
            $table->string('file_mime_type', 100)->nullable()->after('file_data');
            $table->unsignedInteger('file_size')->nullable()->after('file_mime_type');
            $table->dateTime('generated_at')->nullable()->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['file_data', 'file_mime_type', 'file_size', 'generated_at']);
        });
    }
};
