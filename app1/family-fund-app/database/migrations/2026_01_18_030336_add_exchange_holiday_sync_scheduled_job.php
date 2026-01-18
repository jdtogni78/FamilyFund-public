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
        // Create quarterly schedule (1st day of quarter - Jan, Apr, Jul, Oct)
        $scheduleId = DB::table('schedules')->insertGetId([
            'descr' => 'Quarterly - 1st of Quarter',
            'type' => 'DOQ',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create scheduled job for NYSE holiday sync
        DB::table('scheduled_jobs')->insert([
            'schedule_id' => $scheduleId,
            'entity_descr' => 'exchange_holiday_sync',
            'entity_id' => 1, // NYSE exchange ID (placeholder)
            'start_dt' => now()->startOfYear(),
            'end_dt' => '9999-12-31 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Find and delete the scheduled job
        $job = DB::table('scheduled_jobs')
            ->where('entity_descr', 'exchange_holiday_sync')
            ->first();

        if ($job) {
            DB::table('scheduled_jobs')->where('id', $job->id)->delete();
            DB::table('schedules')->where('id', $job->schedule_id)->delete();
        }
    }
};
