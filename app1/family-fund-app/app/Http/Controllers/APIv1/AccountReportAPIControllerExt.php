<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\AccountTrait;
use App\Http\Controllers\Traits\AuthorizesApiAccess;
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
    use AuthorizesApiAccess;

    /** @var  AccountReportRepository */
    private $accountReportRepository;

    public function __construct(AccountReportRepository $AccountReportRepo)
    {
        $this->accountReportRepository = $AccountReportRepo;
    }

    public function store(CreateAccountReportAPIRequest $request)
    {
        $input = $request->all();

        // Generating/sending an account report requires modify rights on the
        // target account (fund-admin / financial-manager).
        $this->requireAccountAccess($this->resolveAccount($input['account_id'] ?? null), modify: true);

        $accountReport = AccountReport::create($input);

        // Dispatch job to send emails
        SendAccountReport::dispatch($accountReport);

        $result = new AccountReportResource($accountReport);
        return $this->sendResponse($result, 'Account Report saved successfully. Email queued for sending.');
    }

    // --- inlined from former base ---

    public function index(Request $request)
    {
        $query = $this->apiAuthz()->scopeByAccountRelation(AccountReport::query());

        foreach ($request->except(['skip', 'limit']) as $field => $value) {
            $query->where($field, $value);
        }

        if ($request->get('skip')) {
            $query->skip((int) $request->get('skip'));
        }

        if ($request->get('limit')) {
            $query->limit((int) $request->get('limit'));
        }

        $accountReports = $query->get();

        return $this->sendResponse(AccountReportResource::collection($accountReports), 'Account Reports retrieved successfully');
    }

    public function show($id)
    {
        /** @var AccountReport $accountReport */
        $accountReport = $this->accountReportRepository->find($id);

        if (empty($accountReport)) {
            return $this->sendError('Account Report not found');
        }

        $this->requireAccountAccess($this->resolveAccount($accountReport->account_id));

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

        $this->requireAccountAccess($this->resolveAccount($accountReport->account_id), modify: true);

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

        $this->requireAccountAccess($this->resolveAccount($accountReport->account_id), modify: true);

        $accountReport->delete();

        return $this->sendSuccess('Account Report deleted successfully');
    }

}
