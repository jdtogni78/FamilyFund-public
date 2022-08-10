<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\FundReportController;
use App\Http\Controllers\Traits\FundTrait;
use App\Http\Requests\CreateFundReportRequest;
use App\Http\Requests\UpdateFundReportRequest;
use App\Jobs\SendFundReport;
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

    /**
     * Store a newly created FundReport in storage.
     *
     * @param CreateFundReportRequest $request
     *
     * @return Response
     */
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

//        if (count($this->err) == 0) {
//            Flash::success('Fund Report saved successfully.');
//        } else {
//            Flash::error(implode("</br>", $this->err));
//        }

}
