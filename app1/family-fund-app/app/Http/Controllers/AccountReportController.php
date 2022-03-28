<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccountReportRequest;
use App\Http\Requests\UpdateAccountReportRequest;
use App\Repositories\AccountReportRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Response;

class AccountReportController extends AppBaseController
{
    /** @var AccountReportRepository $accountReportRepository*/
    protected $accountReportRepository;

    public function __construct(AccountReportRepository $accountReportRepo)
    {
        $this->accountReportRepository = $accountReportRepo;
    }

    /**
     * Display a listing of the AccountReport.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $accountReports = $this->accountReportRepository->all();

        return view('account_reports.index')
            ->with('accountReports', $accountReports);
    }

    /**
     * Show the form for creating a new AccountReport.
     *
     * @return Response
     */
    public function create()
    {
        return view('account_reports.create');
    }

    /**
     * Store a newly created AccountReport in storage.
     *
     * @param CreateAccountReportRequest $request
     *
     * @return Response
     */
    public function store(CreateAccountReportRequest $request)
    {
        $input = $request->all();

        $accountReport = $this->accountReportRepository->create($input);

        Flash::success('Account Report saved successfully.');

        return redirect(route('accountReports.index'));
    }

    /**
     * Display the specified AccountReport.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $accountReport = $this->accountReportRepository->find($id);

        if (empty($accountReport)) {
            Flash::error('Account Report not found');

            return redirect(route('accountReports.index'));
        }

        return view('account_reports.show')->with('accountReport', $accountReport);
    }

    /**
     * Show the form for editing the specified AccountReport.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $accountReport = $this->accountReportRepository->find($id);

        if (empty($accountReport)) {
            Flash::error('Account Report not found');

            return redirect(route('accountReports.index'));
        }

        return view('account_reports.edit')->with('accountReport', $accountReport);
    }

    /**
     * Update the specified AccountReport in storage.
     *
     * @param int $id
     * @param UpdateAccountReportRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAccountReportRequest $request)
    {
        $accountReport = $this->accountReportRepository->find($id);

        if (empty($accountReport)) {
            Flash::error('Account Report not found');

            return redirect(route('accountReports.index'));
        }

        $accountReport = $this->accountReportRepository->update($request->all(), $id);

        Flash::success('Account Report updated successfully.');

        return redirect(route('accountReports.index'));
    }

    /**
     * Remove the specified AccountReport from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $accountReport = $this->accountReportRepository->find($id);

        if (empty($accountReport)) {
            Flash::error('Account Report not found');

            return redirect(route('accountReports.index'));
        }

        $this->accountReportRepository->delete($id);

        Flash::success('Account Report deleted successfully.');

        return redirect(route('accountReports.index'));
    }
}
