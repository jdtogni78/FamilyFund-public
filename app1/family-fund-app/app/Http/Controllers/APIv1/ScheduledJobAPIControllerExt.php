<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Traits\FundTrait;
use App\Http\Controllers\Traits\TransactionTrait;
use App\Http\Requests\API\CreateScheduledJobAPIRequest;
use App\Http\Requests\API\UpdateScheduledJobAPIRequest;
use App\Models\ScheduledJob;
use App\Models\TransactionExt;
use App\Repositories\ScheduledJobRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\ScheduledJobResource;
use Illuminate\Support\Facades\Log;
use Response;

/**
 * Class ScheduledJobController
 * @package App\Http\Controllers\API
 */

class ScheduledJobAPIControllerExt extends AppBaseController
{
    use FundTrait, TransactionTrait;

    // create a registry of handlers
    private $handlers = [];

    // contructor
    public function __construct()
    {
        // create fund report handler
        $this->handlers['fund_report'] = 'fundReportScheduleDue';
        $this->handlers['transaction'] = 'transactionScheduleDue';
    }

    public function scheduleJobs()
    {
        // create fund report schedules repo
        $schedulesRepo = \App::make(ScheduledJobRepository::class);
        $schedules = $schedulesRepo->all();
        $asOf = now();

        /** @var ScheduledJob $schedule */
        foreach ($schedules as $schedule) {
            // log schedule
            Log::info('Checking schedule: ' . json_encode($schedule->toArray()));
            $shouldRunBy = $schedule->shouldRunBy($asOf);

            // if should run by is greater than asof, skip, otherwise report as due
            if ($shouldRunBy->lte($asOf)) {
                $this->scheduleDue($shouldRunBy, $schedule, $asOf);
            } else {
                Log::info('Skip scheduled job ' . $schedule->id . ', due on ' . $shouldRunBy->toDateString());
            }
        }
    }

    private function scheduleDue($shouldRunBy, ScheduledJob $schedule, Carbon $asOf): void
    {
        $func = $this->handlers[$schedule->entity_descr];
        $this->$func($shouldRunBy, $schedule, $asOf);
    }

}
