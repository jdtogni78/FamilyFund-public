<?php

namespace App\Http\Controllers\Traits;

use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

Trait ScheduledJobTrait
{
    use FundTrait, TransactionTrait, TradeBandReportTrait, MatchingReminderTrait, HolidaysSyncTrait, VerboseTrait, ScheduledJobEmailAlertTrait;
    private $handlers = [];

    public function setupHandlers()
    {
        $this->handlers[ScheduledJobExt::ENTITY_FUND_REPORT] = 'fundReportScheduleDue';
        $this->handlers[ScheduledJobExt::ENTITY_TRANSACTION] = 'transactionScheduleDue';
        $this->handlers[ScheduledJobExt::ENTITY_TRADE_BAND_REPORT] = 'tradeBandReportScheduleDue';
        $this->handlers[ScheduledJobExt::ENTITY_MATCHING_REMINDER] = 'matchingReminderScheduleDue';
        $this->handlers[ScheduledJobExt::ENTITY_HOLIDAYS_SYNC] = 'holidaysSyncScheduleDue';
    }

    public function scheduleDueJobs($asOf, $entityDescrFilter=null) {
        // create fund report schedules repo
        $schedules = ScheduledJobExt::all();
        $ret = [];
        $errors = [];

        $this->debug('Checking scheduled jobs: ' . json_encode($schedules->toArray()));

        /** @var ScheduledJob $schedule */
        foreach ($schedules as $schedule) {
            list($model, $error, $shouldRunBy) = $this->scheduleDueJob($asOf, $schedule, $entityDescrFilter);
            if ($model !== null) $ret[] = $model;
            if ($error !== null) $errors[] = $error;
        }
        return [$ret, $errors];
    }

    private function scheduleDueJob($asOf, ScheduledJob $schedule, $entityDescrFilter=null) {
        Log::info('Checking scheduled job: ' . json_encode($schedule->toArray()));
        if ($entityDescrFilter && $schedule->entity_descr != $entityDescrFilter) {
            Log::info('Skip scheduled job ' . $schedule->id . ', entity_descr ' . $schedule->entity_descr . ' does not match filter ' . $entityDescrFilter);
            return;
        }
        /** @var ScheduledJobExt $schedule */
        // $schedule->verbose = true;
        $shouldRunBy = $schedule->shouldRunBy($asOf);
        $shouldRunByDate = $shouldRunBy['shouldRunBy'];

        // if should run by is greater than asof, skip, otherwise report as due
        if ($shouldRunByDate->lte($asOf)) {
            try {
                $model = $this->scheduleDue($shouldRunByDate, $schedule, $asOf);
                if ($model !== null) {
                    Log::info('Scheduled job ' . $schedule->id . ' is due, adding to list');
                    return [$model, null, $shouldRunBy];
                } else {
                    // SOFT FAILURE: Handler returned null (e.g., missing data, template not found)
                    $daysOverdue = $shouldRunByDate->diffInDays($asOf);
                    $reason = "Scheduled job returned null (possible causes: missing data, missing template, or insufficient conditions to execute)";

                    Log::warning("Scheduled job {$schedule->id} soft failure: {$reason}. Days overdue: {$daysOverdue}");

                    // Send email alert for soft failure
                    $this->sendScheduledJobFailureAlert(
                        $schedule,
                        $asOf,
                        $reason,
                        null, // no exception
                        [
                            'should_run_by' => $shouldRunByDate->toDateString(),
                            'days_overdue' => $daysOverdue,
                            'failure_type' => 'soft_failure',
                        ]
                    );

                    // Create a "soft error" exception for tracking
                    $softError = new \Exception($reason);
                    return [null, $softError, $shouldRunBy];
                }
            } catch (\Exception $e) {
                report($e);

                // Send email alert for exception
                $daysOverdue = $shouldRunByDate->diffInDays($asOf);
                $reason = "Scheduled job threw exception: " . $e->getMessage();

                Log::error("Scheduled job {$schedule->id} exception: {$reason}. Days overdue: {$daysOverdue}");

                $this->sendScheduledJobFailureAlert(
                    $schedule,
                    $asOf,
                    $reason,
                    $e,
                    [
                        'should_run_by' => $shouldRunByDate->toDateString(),
                        'days_overdue' => $daysOverdue,
                        'failure_type' => 'exception',
                    ]
                );

                return [null, $e, $shouldRunBy];
            }
        } else {
            Log::info('Skip scheduled job ' . $schedule->id . ', due on ' . $shouldRunByDate->toDateString());
        }
        return [null, null, $shouldRunBy];
    }

    private function scheduleDue($shouldRunByDate, ScheduledJob $schedule, Carbon $asOf, bool $skipDataCheck = false): ?Model
    {
        $this->setupHandlers();
        $func = $this->handlers[$schedule->entity_descr];
        return $this->$func($shouldRunByDate, $schedule, $asOf, $skipDataCheck);
    }

    /**
     * Force run a scheduled job, bypassing the schedule check and optionally the data check
     */
    public function forceRunJob(Carbon $asOf, ScheduledJob $schedule, bool $skipDataCheck = false): array
    {
        Log::info('Force running scheduled job: ' . $schedule->id . ($skipDataCheck ? ' (skipping data check)' : ''));

        try {
            $model = $this->scheduleDue($asOf, $schedule, $asOf, $skipDataCheck);
            if ($model !== null) {
                Log::info('Force run scheduled job ' . $schedule->id . ' succeeded');
                return [$model, null];
            }
            return [null, new \Exception('Job returned no data')];
        } catch (\Exception $e) {
            report($e);
            return [null, $e];
        }
    }

}
