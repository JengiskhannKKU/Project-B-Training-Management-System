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
        // Check if we need to migrate data (old columns exist)
        $hasOldColumns = Schema::hasColumn('training_sessions', 'start_date');

        // For SQLite, we need to recreate the table entirely
        // First, get all existing data
        $sessionsData = [];
        if ($hasOldColumns) {
            $sessionsData = DB::table('training_sessions')->get();
        }

        // Drop the old table
        Schema::dropIfExists('training_sessions');

        // Create the new table with correct structure
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->integer('min_participants')->default(1);
            $table->integer('capacity')->nullable();
            $table->dateTime('registration_start')->nullable();
            $table->dateTime('registration_end')->nullable();
            $table->enum('mode', ['onsite', 'online', 'hybrid'])->default('onsite');
            $table->string('online_link')->nullable();
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->string('location')->nullable();
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });

        // Re-insert data, migrating from old format to new format
        if (!empty($sessionsData)) {
            foreach ($sessionsData as $session) {
                $startAt = null;
                $endAt = null;

                if (isset($session->start_date) && $session->start_date) {
                    $time = $session->start_time ?? '00:00:00';
                    $startAt = $session->start_date . ' ' . $time;
                }
                if (isset($session->end_date) && $session->end_date) {
                    $time = $session->end_time ?? '23:59:59';
                    $endAt = $session->end_date . ' ' . $time;
                }

                // Map old status values to new ones
                $status = $session->status ?? 'scheduled';
                if ($status === 'upcoming') $status = 'scheduled';
                elseif ($status === 'open') $status = 'ongoing';
                elseif ($status === 'closed') $status = 'completed';

                DB::table('training_sessions')->insert([
                    'id' => $session->id,
                    'course_id' => $session->course_id,
                    'title' => $session->title ?? null,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'min_participants' => $session->min_participants ?? 1,
                    'capacity' => $session->max_participants ?? null,
                    'registration_start' => $session->registration_start ?? null,
                    'registration_end' => $session->registration_end ?? null,
                    'mode' => $session->mode ?? 'onsite',
                    'online_link' => $session->online_link ?? null,
                    'trainer_id' => $session->trainer_id,
                    'location' => $session->location ?? null,
                    'status' => $status,
                    'created_at' => $session->created_at ?? now(),
                    'updated_at' => $session->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting is complex, would need to recreate old table structure
        // For now, this is a simplified down migration
        Schema::dropIfExists('training_sessions');
    }
};
