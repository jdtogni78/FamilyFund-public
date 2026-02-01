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

        // Sync multiple years: previous year, current year, and next 2 years
        $startYear = $asOf->year - 1;
        $endYear = $asOf->year + 2;

        Log::info("Running holiday sync for $exchange $startYear-$endYear (job: {$job->id})");

        $totalRecords = 0;
        $source = 'unknown';

        try {
            $syncService = app(HolidaySyncService::class);

            // Sync each year
            for ($year = $startYear; $year <= $endYear; $year++) {
                try {
                    $result = $syncService->syncHolidays($exchange, $year);
                    $totalRecords += $result['records_added'] ?? 0;
                    $source = $result['source'] ?? $source;
                    Log::info("Holiday sync for $year: {$result['records_added']} records");
                } catch (\Exception $e) {
                    Log::warning("Holiday sync failed for $exchange $year: " . $e->getMessage());
                    // Continue with next year even if one fails
                }
            }

            // Log the execution
            $log = HolidaysSyncLog::create([
                'scheduled_job_id' => $job->id,
                'exchange' => $exchange,
                'synced_at' => $asOf,
                'records_synced' => $totalRecords,
                'source' => $source,
            ]);

            Log::info("Holiday sync completed: {$log->records_synced} total records from {$log->source}");

            return $log;
        } catch (\Exception $e) {
            Log::error("Holiday sync failed: " . $e->getMessage());
            throw $e;
        }
    }
}
