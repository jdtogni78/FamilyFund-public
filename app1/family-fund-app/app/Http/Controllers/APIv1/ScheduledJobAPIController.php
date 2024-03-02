<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateScheduledJobAPIRequest;
use App\Http\Requests\API\UpdateScheduledJobAPIRequest;
use App\Models\ScheduledJob;
use App\Repositories\ScheduledJobRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\ScheduledJobResource;
use Illuminate\Support\Facades\Log;
use Response;

/**
 * Class ScheduledJobController
 * @package App\Http\Controllers\API
 */

class ScheduledJobAPIController extends AppBaseController
{
    public function scheduleJobs()
    {
        // create fund report schedules repo
        $schedulesRepo = \App::make(ScheduledJobRepository::class);
        $schedules = $schedulesRepo->all();
        $asOf = now();

        $assetPriceRepo = \App::make(\App\Repositories\AssetPriceRepository::class);

        /** @var ScheduledJob $schedule */
        foreach ($schedules as $schedule) {
            // log schedule
            Log::info('Checking schedule: ' . json_encode($schedule->toArray()));

            // check if there is data to run fund report & is due
            $shouldRunBy = $schedule->shouldRunBy($asOf);
            $hasNewAssets = $assetPriceRepo->makeModel()->newQuery()
                ->whereDate('start_dt', '>=', $shouldRunBy)->limit(1)->count();

            // if should run by is greater than asof, skip, otherwise create fund report
            if ($shouldRunBy->lte($asOf)) {
                if ($hasNewAssets > 0) {
                    Log::info('Creating fund report for schedule: ' . $schedule->id);
                    $fundReport = $this->createFundReportFromSchedule($schedule, $asOf, $shouldRunBy);
                } else {
                    Log::warning('Missing data for fund report schedule ' . $schedule->id);
                }
            } else {
                Log::info('Skipping fund report for schedule ' . $schedule->id . ', due on ' . $shouldRunBy->toDateString());
            }
        }
        return;
    }

}
