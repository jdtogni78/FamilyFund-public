<?php

namespace App\Http\Controllers\Traits;

use App\Models\HolidaysSyncLog;
use App\Models\ScheduledJob;
use App\Services\HolidaySyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

trait HolidaysSyncTrait
{
    protected function holidaysSyncScheduleDue(
        $shouldRunBy,
        ScheduledJob $job,
        Carbon $asOf,
        bool $skipDataCheck = false
    ): ?HolidaysSyncLog {
        // Get exchange from job config (default to NYSE)
        // Could extend to use entity_id to reference an exchange config table in future
        $exchange = 'NYSE';
        $year = $asOf->year;

        Log::info("Running holiday sync for $exchange $year (job: {$job->id})");

        try {
            // Execute the sync via service
            $syncService = app(HolidaySyncService::class);
            $result = $syncService->syncHolidays($exchange, $year);

            // Log the execution
            $log = HolidaysSyncLog::create([
                'scheduled_job_id' => $job->id,
                'exchange' => $exchange,
                'synced_at' => $asOf,
                'records_synced' => $result['records_added'] ?? 0,
                'source' => $result['source'] ?? 'unknown',
            ]);

            Log::info("Holiday sync completed: {$log->records_synced} records from {$log->source}");

            return $log;
        } catch (\Exception $e) {
            Log::error("Holiday sync failed: " . $e->getMessage());
            throw $e;
        }
    }
}
