<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\Traits\FundTrait;
use App\Http\Controllers\Traits\ScheduledJobTrait;
use App\Http\Controllers\Traits\TransactionTrait;
use App\Http\Controllers\API\ScheduledJobAPIController;
use App\Repositories\ScheduledJobRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Class ScheduledJobController
 * @package App\Http\Controllers\API
 */

class ScheduledJobAPIControllerExt extends ScheduledJobAPIController
{
    use ScheduledJobTrait;

    // contructor
    public function __construct(ScheduledJobRepository $scheduledJobRepo)
    {
        parent::__construct($scheduledJobRepo);
        $this->setupHandlers();
    }

    public function scheduleJobs(Request $request)
    {
        $asOfInput = $request->input('as_of', Carbon::now());
        $asOf = $asOfInput instanceof Carbon ? $asOfInput : Carbon::parse($asOfInput);
        $entityDescrFilter = $request->input('entity_descr', null);
        list ($ret, $errors) = $this->scheduleDueJobs($asOf, $entityDescrFilter);
        if (count($errors) > 0) {
            return $this->sendError('Errors scheduling jobs: ' . implode(', ', $errors));
        }
        return $this->sendResponse($ret, 'Scheduled jobs retrieved successfully');
    }
}
