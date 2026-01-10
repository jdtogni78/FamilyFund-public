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

        // Dispatch job to send emails
        SendAccountReport::dispatch($accountReport);

        $result = new AccountReportResource($accountReport);
        return $this->sendResponse($result, 'Account Report saved successfully. Email queued for sending.');
    }

}
