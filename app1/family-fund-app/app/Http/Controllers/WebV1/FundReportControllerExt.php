<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\FundReportController;
use App\Http\Controllers\Traits\FundTrait;
use App\Http\Requests\CreateFundReportRequest;
use App\Http\Requests\UpdateFundReportRequest;
use App\Jobs\SendFundReport;
use App\Models\FundReportExt;
use App\Repositories\FundReportRepository;
use Laracasts\Flash\Flash;
use Response;

class FundReportControllerExt extends FundReportController
{
    use FundTrait;

    public function __construct(FundReportRepository $fundReportRepo)
    {
        parent::__construct($fundReportRepo);
    }

    public function create()
    {
        $api = [
            'typeMap' => FundReportExt::$typeMap,
        ];

        return view('fund_reports.create')
            ->with('api', $api);
    }

    public function edit($id)
    {
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            Flash::error('Fund Report not found');

            return redirect(route('fundReports.index'));
        }
        $api = [
            'typeMap' => FundReportExt::$typeMap,
        ];

        return view('fund_reports.edit')
            ->with('fundReport', $fundReport)
            ->with('api', $api);
    }

    public function store(CreateFundReportRequest $request)
    {
        try {
            $fundReport = $this->createFundReport($request->all());
            SendFundReport::dispatch($fundReport);
        } catch (Exception $e) {
            report($e);
            Flash::error($e->getMessage());
        }

        return redirect(route('fundReports.index'));
    }

    public function update($id, UpdateFundReportRequest $request)
    {
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            Flash::error('Fund Report not found');

            return redirect(route('fundReports.index'));
        }

        $fundReport = $this->fundReportRepository->update($request->all(), $id);
        SendFundReport::dispatch($fundReport);

        return redirect(route('fundReports.index'));
    }

}
