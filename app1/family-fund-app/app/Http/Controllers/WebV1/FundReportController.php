<?php

namespace App\Http\Controllers;

use App\Http\Controllers\APIv1\PortfolioAPIControllerExt;
use App\Http\Controllers\WebV1\FundControllerExt;
use App\Http\Controllers\WebV1\FundPDF;
use App\Http\Requests\CreateFundReportRequest;
use App\Http\Requests\UpdateFundReportRequest;
use App\Mail\FundQuarterlyReport;
use App\Models\FundReport;
use App\Repositories\FundReportRepository;
use App\Http\Controllers\AppBaseController;
use App\Repositories\PortfolioRepository;
use Illuminate\Http\Request;
use Flash;
use Response;
use Illuminate\Support\Facades\Mail;

class FundReportControllerExt extends FundReportController
{
    public function __construct(FundReportRepository $fundReportRepo)
    {
        $this->fundReportRepository = $fundReportRepo;
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
        $input = $request->all();

        $fundReport = $this->fundReportRepository->create($input);

        Flash::success('Fund Report saved successfully.');

        $fund = $fundReport->fund()->first();
        $asOf = $fundReport->as_of;
        $isAdmin = 'ADM' === $fundReport->type;

        $fundController = new FundControllerExt(\App::make(FundRepository::class));
        $arr = $fundController->createFundViewData($fund, $asOf, $isAdmin);
        $pdf = new FundPDF($arr, $isAdmin);

        foreach ($fund->accounts() as $account) {
            if (
                ($isAdmin && count($account->user()) == 0) ||
                (!$isAdmin && count($account->user()) == 1)
            ) {
                Mail::to($account->email_cc)->send(new FundQuarterlyReport($pdf->file()));

            }
        }


        return redirect(route('fundReports.index'));
    }
}
