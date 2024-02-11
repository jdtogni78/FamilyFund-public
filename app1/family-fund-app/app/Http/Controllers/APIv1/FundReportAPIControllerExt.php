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

        foreach ($schedules as $schedule) {
            // check if schedule is due
            if ($schedule->isDue($schedule)) {
                // create fund report
                $fundReport = $this->createFundReportFromSchedule($schedule);
                $this->msgs[] = 'Fund Report saved successfully';
            }
            $fundReport = $this->createFundReport($schedule->toArray());
            $this->msgs[] = 'Fund Report saved successfully';
        }
        return $fundReport;
    }
}
