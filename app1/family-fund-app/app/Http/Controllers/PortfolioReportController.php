<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePortfolioReportRequest;
use App\Http\Requests\UpdatePortfolioReportRequest;
use App\Repositories\PortfolioReportRepository;
use Illuminate\Http\Request;
use Flash;

use Response;

class PortfolioReportController extends AppBaseController
{
    /** @var PortfolioReportRepository $portfolioReportRepository*/
    protected $portfolioReportRepository;

    public function __construct(PortfolioReportRepository $portfolioReportRepo)
    {
        $this->portfolioReportRepository = $portfolioReportRepo;
    }

    /**
     * Display a listing of the PortfolioReport.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $portfolioReports = $this->portfolioReportRepository->all()
            ->sortByDesc('end_date');

        return view('portfolio_reports.index')
            ->with('portfolioReports', $portfolioReports);
    }

    /**
     * Show the form for creating a new PortfolioReport.
     *
     * @return Response
     */
    public function create()
    {
        return view('portfolio_reports.create');
    }

    /**
     * Store a newly created PortfolioReport in storage.
     *
     * @param CreatePortfolioReportRequest $request
     *
     * @return Response
     */
    public function store(CreatePortfolioReportRequest $request)
    {
        $input = $request->all();

        $portfolioReport = $this->portfolioReportRepository->create($input);

        Flash::success('Portfolio Report saved successfully.');

        return redirect(route('portfolioReports.index'));
    }

    /**
     * Display the specified PortfolioReport.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $portfolioReport = $this->portfolioReportRepository->find($id);

        if (empty($portfolioReport)) {
            Flash::error('Portfolio Report not found');

            return redirect(route('portfolioReports.index'));
        }

        return view('portfolio_reports.show')->with('portfolioReport', $portfolioReport);
    }

    /**
     * Show the form for editing the specified PortfolioReport.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $portfolioReport = $this->portfolioReportRepository->find($id);

        if (empty($portfolioReport)) {
            Flash::error('Portfolio Report not found');

            return redirect(route('portfolioReports.index'));
        }

        return view('portfolio_reports.edit')->with('portfolioReport', $portfolioReport);
    }

    /**
     * Update the specified PortfolioReport in storage.
     *
     * @param int $id
     * @param UpdatePortfolioReportRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePortfolioReportRequest $request)
    {
        $portfolioReport = $this->portfolioReportRepository->find($id);

        if (empty($portfolioReport)) {
            Flash::error('Portfolio Report not found');

            return redirect(route('portfolioReports.index'));
        }

        $portfolioReport = $this->portfolioReportRepository->update($request->all(), $id);

        Flash::success('Portfolio Report updated successfully.');

        return redirect(route('portfolioReports.index'));
    }

    /**
     * Remove the specified PortfolioReport from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $portfolioReport = $this->portfolioReportRepository->find($id);

        if (empty($portfolioReport)) {
            Flash::error('Portfolio Report not found');

            return redirect(route('portfolioReports.index'));
        }

        $this->portfolioReportRepository->delete($id);

        Flash::success('Portfolio Report deleted successfully.');

        return redirect(route('portfolioReports.index'));
    }
}
