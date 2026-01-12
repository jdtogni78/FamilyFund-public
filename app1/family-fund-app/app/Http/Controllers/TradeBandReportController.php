<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTradeBandReportRequest;
use App\Http\Requests\UpdateTradeBandReportRequest;
use App\Jobs\SendTradeBandReport;
use App\Models\TradeBandReport;
use App\Repositories\TradeBandReportRepository;
use App\Models\FundExt;
use Illuminate\Http\Request;
use Flash;
use Response;

class TradeBandReportController extends AppBaseController
{
    /** @var TradeBandReportRepository $tradeBandReportRepository */
    protected $tradeBandReportRepository;

    public function __construct(TradeBandReportRepository $tradeBandReportRepo)
    {
        $this->tradeBandReportRepository = $tradeBandReportRepo;
    }

    /**
     * Display a listing of the TradeBandReport.
     */
    public function index(Request $request)
    {
        $tradeBandReports = $this->tradeBandReportRepository->all()
            ->sortByDesc('as_of');

        return view('trade_band_reports.index')
            ->with('tradeBandReports', $tradeBandReports);
    }

    /**
     * Show the form for creating a new TradeBandReport.
     */
    public function create()
    {
        $api = [
            'fundMap' => FundExt::fundMap(),
        ];
        return view('trade_band_reports.create')->with('api', $api);
    }

    /**
     * Store a newly created TradeBandReport in storage.
     */
    public function store(CreateTradeBandReportRequest $request)
    {
        $input = $request->all();

        $tradeBandReport = $this->tradeBandReportRepository->create($input);

        Flash::success('Trade Band Report saved successfully.');

        return redirect(route('tradeBandReports.index'));
    }

    /**
     * Display the specified TradeBandReport.
     */
    public function show($id)
    {
        $tradeBandReport = $this->tradeBandReportRepository->find($id);

        if (empty($tradeBandReport)) {
            Flash::error('Trade Band Report not found');
            return redirect(route('tradeBandReports.index'));
        }

        return view('trade_band_reports.show')->with('tradeBandReport', $tradeBandReport);
    }

    /**
     * Show the form for editing the specified TradeBandReport.
     */
    public function edit($id)
    {
        $tradeBandReport = $this->tradeBandReportRepository->find($id);

        if (empty($tradeBandReport)) {
            Flash::error('Trade Band Report not found');
            return redirect(route('tradeBandReports.index'));
        }

        $api = [
            'fundMap' => FundExt::fundMap(),
        ];
        return view('trade_band_reports.edit')
            ->with('tradeBandReport', $tradeBandReport)
            ->with('api', $api);
    }

    /**
     * Update the specified TradeBandReport in storage.
     */
    public function update($id, UpdateTradeBandReportRequest $request)
    {
        $tradeBandReport = $this->tradeBandReportRepository->find($id);

        if (empty($tradeBandReport)) {
            Flash::error('Trade Band Report not found');
            return redirect(route('tradeBandReports.index'));
        }

        $tradeBandReport = $this->tradeBandReportRepository->update($request->all(), $id);

        Flash::success('Trade Band Report updated successfully.');

        return redirect(route('tradeBandReports.index'));
    }

    /**
     * Remove the specified TradeBandReport from storage.
     */
    public function destroy($id)
    {
        $tradeBandReport = $this->tradeBandReportRepository->find($id);

        if (empty($tradeBandReport)) {
            Flash::error('Trade Band Report not found');
            return redirect(route('tradeBandReports.index'));
        }

        $this->tradeBandReportRepository->delete($id);

        Flash::success('Trade Band Report deleted successfully.');

        return redirect(route('tradeBandReports.index'));
    }

    /**
     * View PDF for the specified TradeBandReport.
     */
    public function viewPdf($id)
    {
        $tradeBandReport = $this->tradeBandReportRepository->find($id);

        if (empty($tradeBandReport)) {
            Flash::error('Trade Band Report not found');
            return redirect(route('tradeBandReports.index'));
        }

        return redirect(route('funds.show_trade_bands_pdf', [
            'id' => $tradeBandReport->fund_id,
            'as_of' => $tradeBandReport->as_of->format('Y-m-d')
        ]));
    }

    /**
     * Resend the specified TradeBandReport via email.
     */
    public function resend($id)
    {
        $tradeBandReport = TradeBandReport::find($id);

        if (empty($tradeBandReport)) {
            Flash::error('Trade Band Report not found');
            return redirect(route('tradeBandReports.index'));
        }

        if ($tradeBandReport->as_of->format('Y') === '9999') {
            Flash::error('Cannot resend a template');
            return redirect(route('tradeBandReports.index'));
        }

        SendTradeBandReport::dispatch($tradeBandReport);
        Flash::success('Trade Band Report #' . $id . ' queued for resending.');

        return redirect(route('tradeBandReports.index'));
    }
}
