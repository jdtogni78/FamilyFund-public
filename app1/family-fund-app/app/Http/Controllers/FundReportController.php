<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFundReportRequest;
use App\Http\Requests\UpdateFundReportRequest;
use App\Repositories\FundReportRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class FundReportController extends AppBaseController
{
    /** @var FundReportRepository $fundReportRepository*/
    private $fundReportRepository;

    public function __construct(FundReportRepository $fundReportRepo)
    {
        $this->fundReportRepository = $fundReportRepo;
    }

    /**
     * Display a listing of the FundReport.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $fundReports = $this->fundReportRepository->all();

        return view('fund_reports.index')
            ->with('fundReports', $fundReports);
    }

    /**
     * Show the form for creating a new FundReport.
     *
     * @return Response
     */
    public function create()
    {
        return view('fund_reports.create');
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

        return redirect(route('fundReports.index'));
    }

    /**
     * Display the specified FundReport.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            Flash::error('Fund Report not found');

            return redirect(route('fundReports.index'));
        }

        return view('fund_reports.show')->with('fundReport', $fundReport);
    }

    /**
     * Show the form for editing the specified FundReport.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            Flash::error('Fund Report not found');

            return redirect(route('fundReports.index'));
        }

        return view('fund_reports.edit')->with('fundReport', $fundReport);
    }

    /**
     * Update the specified FundReport in storage.
     *
     * @param int $id
     * @param UpdateFundReportRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateFundReportRequest $request)
    {
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            Flash::error('Fund Report not found');

            return redirect(route('fundReports.index'));
        }

        $fundReport = $this->fundReportRepository->update($request->all(), $id);

        Flash::success('Fund Report updated successfully.');

        return redirect(route('fundReports.index'));
    }

    /**
     * Remove the specified FundReport from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            Flash::error('Fund Report not found');

            return redirect(route('fundReports.index'));
        }

        $this->fundReportRepository->delete($id);

        Flash::success('Fund Report deleted successfully.');

        return redirect(route('fundReports.index'));
    }
}
