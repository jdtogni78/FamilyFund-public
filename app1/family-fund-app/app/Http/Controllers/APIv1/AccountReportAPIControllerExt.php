<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\AccountTrait;
use App\Http\Requests\API\CreateAccountReportAPIRequest;
use App\Http\Requests\API\UpdateAccountReportAPIRequest;
use App\Http\Resources\AccountReportResource;
use App\Jobs\SendAccountReport;
use App\Models\AccountReport;
use App\Repositories\AccountReportRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
* Class AccountReportAPIControllerExt
* @package App\Http\Controllers\API
*/
class AccountReportAPIControllerExt extends AppBaseController
{
    use AccountTrait;

    /** @var  AccountReportRepository */
    private $accountReportRepository;

    public function __construct(AccountReportRepository $AccountReportRepo)
    {
        $this->accountReportRepository = $AccountReportRepo;
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

    // --- inlined from former base ---

    public function index(Request $request)
    {
        $accountReports = $this->accountReportRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(AccountReportResource::collection($accountReports), 'Account Reports retrieved successfully');
    }

    public function show($id)
    {
        /** @var AccountReport $accountReport */
        $accountReport = $this->accountReportRepository->find($id);

        if (empty($accountReport)) {
            return $this->sendError('Account Report not found');
        }

        return $this->sendResponse(new AccountReportResource($accountReport), 'Account Report retrieved successfully');
    }

    public function update($id, UpdateAccountReportAPIRequest $request)
    {
        $input = $request->all();

        /** @var AccountReport $accountReport */
        $accountReport = $this->accountReportRepository->find($id);

        if (empty($accountReport)) {
            return $this->sendError('Account Report not found');
        }

        $accountReport = $this->accountReportRepository->update($input, $id);

        return $this->sendResponse(new AccountReportResource($accountReport), 'AccountReport updated successfully');
    }

    public function destroy($id)
    {
        /** @var AccountReport $accountReport */
        $accountReport = $this->accountReportRepository->find($id);

        if (empty($accountReport)) {
            return $this->sendError('Account Report not found');
        }

        $accountReport->delete();

        return $this->sendSuccess('Account Report deleted successfully');
    }

}
