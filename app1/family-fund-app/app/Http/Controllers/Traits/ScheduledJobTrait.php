<?php

namespace App\Http\Controllers\Traits;

use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use App\Repositories\ScheduledJobRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

Trait ScheduledJobTrait
{
    use FundTrait, TransactionTrait, VerboseTrait;
    private $handlers = [];

    public function setupHandlers()
    {
        // create fund report handler
        $this->handlers[ScheduledJobExt::ENTITY_FUND_REPORT] = 'fundReportScheduleDue';
        $this->handlers[ScheduledJobExt::ENTITY_TRANSACTION] = 'transactionScheduleDue';
    }

    public function scheduleDueJobs($asOf, $entityDescrFilter=null) {
        // create fund report schedules repo
        $schedules = ScheduledJobExt::all();
        $ret = [];
        $errors = [];

        $this->debug('Checking scheduled jobs: ' . json_encode($schedules->toArray()));

        /** @var ScheduledJob $schedule */
        foreach ($schedules as $schedule) {
            Log::info('Checking scheduled job: ' . json_encode($schedule->toArray()));
            if ($entityDescrFilter && $schedule->entity_descr != $entityDescrFilter) {
                Log::info('Skip scheduled job ' . $schedule->id . ', entity_descr ' . $schedule->entity_descr . ' does not match filter ' . $entityDescrFilter);
                continue;
            }
            $shouldRunBy = $schedule->shouldRunBy($asOf);

            // if should run by is greater than asof, skip, otherwise report as due
            if ($shouldRunBy->lte($asOf)) {
                try {
                    $model = $this->scheduleDue($shouldRunBy, $schedule, $asOf);
                    if ($model !== null) $ret[] = $model;
                } catch (\Exception $e) {
                    report($e);
                    $errors[] = $e->getMessage();
                }
            } else {
                Log::info('Skip scheduled job ' . $schedule->id . ', due on ' . $shouldRunBy->toDateString());
            }
        }
        return array($ret, $errors);
    }

    private function scheduleDue($shouldRunBy, ScheduledJob $schedule, Carbon $asOf): ?Model
    {
        $func = $this->handlers[$schedule->entity_descr];
        return $this->$func($shouldRunBy, $schedule, $asOf);
    }

}
