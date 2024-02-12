<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\Traits\FundTrait;
use App\Http\Requests\API\CreateFundReportAPIRequest;
use App\Http\Resources\FundReportResource;
use App\Models\FundReportSchedule;
use App\Repositories\FundReportRepository;
use App\Http\Controllers\API\FundReportAPIController;
use App\Repositories\FundReportScheduleRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FundReportAPIControllerExt
 * @package App\Http\Controllers\API
 */

class FundReportAPIControllerExt extends FundReportAPIController
{
    use FundTrait;
    public $verbose = false;

    public function __construct(FundReportRepository $fundReportRepo)
    {
        parent::__construct($fundReportRepo);
    }

    public function store(CreateFundReportAPIRequest $request)
    {
        try {
            $input = $request->all();
            $fundReport = $this->createFundReport($input);
            $result = new FundReportResource($fundReport);
            return $this->sendResponse($result, 'Fund Report saved successfully' . "\n" . implode($this->msgs));
        } catch (Exception $e) {
            report($e);
            return $this->sendError($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function scheduleReports()
    {
        // create fund report schedules repo
        $schedulesRepo = \App::make(FundReportScheduleRepository::class);
        $schedules = $schedulesRepo->all();
        $asOf = now();

        $assetPriceRepo = \App::make(\App\Repositories\AssetPriceRepository::class);

        /** @var FundReportSchedule $schedule */
        foreach ($schedules as $schedule) {
            // log schedule
            Log::info('Checking schedule: ' . json_encode($schedule->toArray()));

            // check if schedule is due
            $shouldRunBy = $schedule->shouldRunBy($asOf, $schedule);
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

    private function createFundReportFromSchedule(mixed $schedule, $asOf, $shouldRunBy)
    {
        $templateReport = $schedule->fundReportTemplate()->first();
        $fundReport = $this->createFundReport([
            'fund_id' => $templateReport->fund_id,
            'type' => $templateReport->type,
            'as_of' => $shouldRunBy,
            'fund_report_schedule_id' => $schedule->id,
            'created_at' => $asOf,
        ]);
        Log::info('Created fund report from schedule: ' . json_encode($fundReport));
        return $fundReport;
    }
}
