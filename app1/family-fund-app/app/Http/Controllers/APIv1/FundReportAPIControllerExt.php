<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\Traits\FundTrait;
use App\Http\Requests\API\CreateFundReportAPIRequest;
use App\Http\Resources\FundReportResource;
use App\Repositories\FundReportRepository;
use App\Http\Controllers\API\FundReportAPIController;
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
        $input = $request->all();

        $fundReport = $this->sendFundReport($input);

        if (count($this->err) == 0) {
            $result = new FundReportResource($fundReport);
//            print_r("result: " . json_encode($result->toArray($request)) . "\n");
            return $this->sendResponse($result, 'Fund Report saved successfully'."\n".implode($this->msgs));
        } else {
            return $this->sendError(implode(",", $this->err), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
