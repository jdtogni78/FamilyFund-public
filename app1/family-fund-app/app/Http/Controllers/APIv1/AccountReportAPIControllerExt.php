<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\API\AccountReportAPIController;
use App\Http\Controllers\Traits\AccountTrait;
use App\Http\Requests\API\CreateAccountReportAPIRequest;
use App\Http\Resources\AccountReportResource;
use App\Jobs\SendAccountReport;
use App\Models\AccountReport;
use App\Repositories\AccountReportRepository;
use Symfony\Component\HttpFoundation\Response;

/**
* Class AccountReportAPIControllerExt
* @package App\Http\Controllers\API
*/
class AccountReportAPIControllerExt extends AccountReportAPIController
{
    use AccountTrait;

    public function __construct(AccountReportRepository $AccountReportRepo)
    {
        parent::__construct($AccountReportRepo);
    }

    public function store(CreateAccountReportAPIRequest $request)
    {
        $input = $request->all();

        $accountReport = AccountReport::create($input);
        SendAccountReport::dispatch($accountReport);

        if (count($this->err) == 0) {
            $result = new AccountReportResource($accountReport);
//            print_r("result: " . json_encode($result->toArray($request)) . "\n");
            return $this->sendResponse($result, 'Account Report saved successfully'."\n".implode($this->msgs));
        } else {
            return $this->sendError(implode(",", $this->err), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

}
