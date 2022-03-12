<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateAccountReportAPIRequest;
use App\Http\Requests\API\UpdateAccountReportAPIRequest;
use App\Models\AccountReport;
use App\Repositories\AccountReportRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\AccountReportResource;
use Response;

/**
 * Class AccountReportController
 * @package App\Http\Controllers\API
 */

class AccountReportAPIController extends AppBaseController
{
    /** @var  AccountReportRepository */
    private $accountReportRepository;

    public function __construct(AccountReportRepository $accountReportRepo)
    {
        $this->accountReportRepository = $accountReportRepo;
    }

    /**
     * Display a listing of the AccountReport.
     * GET|HEAD /accountReports
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $accountReports = $this->accountReportRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(AccountReportResource::collection($accountReports), 'Account Reports retrieved successfully');
    }

    /**
     * Store a newly created AccountReport in storage.
     * POST /accountReports
     *
     * @param CreateAccountReportAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateAccountReportAPIRequest $request)
    {
        $input = $request->all();

        $accountReport = $this->accountReportRepository->create($input);

        return $this->sendResponse(new AccountReportResource($accountReport), 'Account Report saved successfully');
    }

    /**
     * Display the specified AccountReport.
     * GET|HEAD /accountReports/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var AccountReport $accountReport */
        $accountReport = $this->accountReportRepository->find($id);

        if (empty($accountReport)) {
            return $this->sendError('Account Report not found');
        }

        return $this->sendResponse(new AccountReportResource($accountReport), 'Account Report retrieved successfully');
    }

    /**
     * Update the specified AccountReport in storage.
     * PUT/PATCH /accountReports/{id}
     *
     * @param int $id
     * @param UpdateAccountReportAPIRequest $request
     *
     * @return Response
     */
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

    /**
     * Remove the specified AccountReport from storage.
     * DELETE /accountReports/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
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
