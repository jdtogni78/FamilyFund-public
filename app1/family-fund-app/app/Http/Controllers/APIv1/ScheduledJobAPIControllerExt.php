<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Traits\FundTrait;
use App\Http\Controllers\Traits\ScheduledJobTrait;
use App\Http\Controllers\Traits\TransactionTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;

/**
 * Class ScheduledJobController
 * @package App\Http\Controllers\API
 */

class ScheduledJobAPIControllerExt extends AppBaseController
{
    use ScheduledJobTrait;

    // contructor
    public function __construct()
    {
        $this->setupHandlers();
    }

    public function scheduleJobs(Request $request)
    {
        $asOf = $request->input('as_of', Carbon::now());
        list ($ret, $errors) = $this->scheduleDueJobs($asOf);
        if (count($errors) > 0) {
            return $this->sendError('Errors scheduling jobs: ' . implode(', ', $errors));
        }
        return $this->sendResponse($ret, 'Scheduled jobs retrieved successfully');
    }
}
