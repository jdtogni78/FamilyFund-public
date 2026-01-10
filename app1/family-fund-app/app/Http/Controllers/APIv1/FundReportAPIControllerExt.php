<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\Traits\FundTrait;
use App\Http\Requests\API\CreateFundReportAPIRequest;
use App\Http\Resources\FundReportResource;
use App\Jobs\SendFundReport;
use App\Models\FundReportExt;
use App\Models\ScheduledJob;
use App\Repositories\FundReportRepository;
use App\Http\Controllers\API\FundReportAPIController;
use App\Repositories\ScheduledJobRepository;
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

    public function __construct(FundReportRepository $fundReportRepo)
    {
        parent::__construct($fundReportRepo);
    }

    public function store(CreateFundReportAPIRequest $request)
    {
        try {
            $input = $request->all();

            // Create fund report and validate emails
            $fundReport = FundReportExt::create($input);
            $this->validateReportEmails($fundReport);
            $fundReport->save();

            // Dispatch job to send emails
            SendFundReport::dispatch($fundReport);

            $result = new FundReportResource($fundReport);
            return $this->sendResponse($result, 'Fund Report saved successfully. Email queued for sending.');
        } catch (Exception $e) {
            report($e);
            return $this->sendError($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
