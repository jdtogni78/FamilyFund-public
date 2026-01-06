<?php

namespace App\Http\Controllers\Traits;

use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

Trait ScheduledJobTrait
{
    use FundTrait, TransactionTrait, PortfolioReportTrait, VerboseTrait;
    private $handlers = [];

    public function setupHandlers()
    {
        // create fund report handler
        $this->handlers[ScheduledJobExt::ENTITY_FUND_REPORT] = 'fundReportScheduleDue';
        $this->handlers[ScheduledJobExt::ENTITY_TRANSACTION] = 'transactionScheduleDue';
        $this->handlers[ScheduledJobExt::ENTITY_PORTFOLIO_REPORT] = 'portfolioReportScheduleDue';
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
                }
            } catch (\Exception $e) {
                report($e);
                return [null, $e, $shouldRunBy];
            }
        } else {
            Log::info('Skip scheduled job ' . $schedule->id . ', due on ' . $shouldRunByDate->toDateString());
        }
        return [null, null, $shouldRunBy];
    }

    private function scheduleDue($shouldRunByDate, ScheduledJob $schedule, Carbon $asOf): ?Model
    {
        $this->setupHandlers();
        $func = $this->handlers[$schedule->entity_descr];
        return $this->$func($shouldRunByDate, $schedule, $asOf);
    }

}
