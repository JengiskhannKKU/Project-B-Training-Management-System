<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add new columns
        Schema::table('training_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('training_sessions', 'start_at')) {
                $table->dateTime('start_at')->nullable()->after('title');
            }
            if (!Schema::hasColumn('training_sessions', 'end_at')) {
                $table->dateTime('end_at')->nullable()->after('start_at');
            }
        });

        // 2. Migrate data (only if source columns exist)
        if (Schema::hasColumn('training_sessions', 'start_date')) {
             DB::statement("
                UPDATE training_sessions 
                SET start_at = CAST(CONCAT(start_date, ' ', COALESCE(start_time, '00:00:00')) AS DATETIME), 
                    end_at = CAST(CONCAT(end_date, ' ', COALESCE(end_time, '23:59:59')) AS DATETIME)
            ");
        }

        // 3. Drop old columns and rename/modify
        Schema::table('training_sessions', function (Blueprint $table) {
            $columnsToDrop = [];
            $candidates = [
                'start_date', 'end_date', 'start_time', 'end_time',
                'trainer_name', 'trainer_photo_url',
                'approval_status', 'approved_by', 'approved_at', 'approval_note'
            ];

            foreach ($candidates as $col) {
                if (Schema::hasColumn('training_sessions', $col)) {
                    $columnsToDrop[] = $col;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            
            if (Schema::hasColumn('training_sessions', 'max_participants') && !Schema::hasColumn('training_sessions', 'capacity')) {
                $table->renameColumn('max_participants', 'capacity');
            }
        });

        // 4. Update Status Enum
        DB::statement("UPDATE training_sessions SET status = 'scheduled' WHERE status = 'upcoming'");
        DB::statement("UPDATE training_sessions SET status = 'ongoing' WHERE status = 'open'");
        DB::statement("UPDATE training_sessions SET status = 'completed' WHERE status = 'closed'");
        
        Schema::table('training_sessions', function (Blueprint $table) {
             $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])
                   ->default('scheduled')
                   ->change();
        });
        
        // 5. Make title optional (nullable)
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ... (Simplified down for now, as reversing a partial fail is hard)
    }
};
