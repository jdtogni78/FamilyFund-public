<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AccountReportController;
use App\Http\Requests\CreateAccountReportRequest;
use App\Http\Requests\UpdateAccountReportRequest;
use App\Jobs\SendAccountReport;
use App\Models\AccountReport;
use App\Models\AccountReportExt;
use App\Repositories\AccountReportRepository;
use Laracasts\Flash\Flash;
use Response;

class AccountReportControllerExt extends AccountReportController
{
    public function __construct(AccountReportRepository $accountReportRepository)
    {
        parent::__construct($accountReportRepository);
    }

    public function create()
    {
        $api = ['typeMap' => AccountReportExt::$typeMap];
        return parent::create()->with('api', $api);
    }

    public function show($id)
    {
        $api = ['typeMap' => AccountReportExt::$typeMap];
        return parent::show($id)->with('api', $api);
    }

    public function store(CreateAccountReportRequest $request)
    {
        $input = $request->all();

        $accountReport = AccountReport::create($input);
        SendAccountReport::dispatch($accountReport);

        return redirect(route('accountReports.index'));
    }

    public function update($id, UpdateAccountReportRequest $request)
    {
        $AccountReport = $this->accountReportRepository->find($id);

        if (empty($AccountReport)) {
            Flash::error('Account Report not found');

            return redirect(route('accountReports.index'));
        }

        $accountReport = $this->accountReportRepository->update($request->all(), $id);
        SendAccountReport::dispatch($accountReport);

        return redirect(route('accountReports.index'));
    }
}
