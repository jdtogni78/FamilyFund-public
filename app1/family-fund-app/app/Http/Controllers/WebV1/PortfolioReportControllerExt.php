<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\PortfolioReportController;
use App\Http\Controllers\Traits\PortfolioReportTrait;
use App\Http\Requests\CreatePortfolioReportRequest;
use App\Http\Requests\UpdatePortfolioReportRequest;
use App\Jobs\SendPortfolioReport;
use App\Models\PortfolioExt;
use App\Models\PortfolioReportExt;
use App\Repositories\PortfolioReportRepository;
use App\Repositories\PortfolioRepository;
use Laracasts\Flash\Flash;
use Response;

class PortfolioReportControllerExt extends PortfolioReportController
{
    use PortfolioReportTrait;

    protected $portfolioRepository;

    public function __construct(PortfolioReportRepository $portfolioReportRepo, PortfolioRepository $portfolioRepo)
    {
        parent::__construct($portfolioReportRepo);
        $this->portfolioRepository = $portfolioRepo;
    }

    public function create()
    {
        $portfolios = $this->portfolioRepository->all()->pluck('name', 'id');

        $api = [
            'portfolios' => $portfolios,
            'typeMap' => PortfolioReportExt::$typeMap,
        ];

        return view('portfolio_reports.create')
            ->with('api', $api);
    }

    public function edit($id)
    {
        $portfolioReport = $this->portfolioReportRepository->find($id);

        if (empty($portfolioReport)) {
            Flash::error('Portfolio Report not found');

            return redirect(route('portfolioReports.index'));
        }

        $portfolios = $this->portfolioRepository->all()->pluck('name', 'id');

        $api = [
            'portfolios' => $portfolios,
            'typeMap' => PortfolioReportExt::$typeMap,
        ];

        return view('portfolio_reports.edit')
            ->with('portfolioReport', $portfolioReport)
            ->with('api', $api);
    }

    public function store(CreatePortfolioReportRequest $request)
    {
        try {
            $portfolioReport = $this->createPortfolioReport($request->all());
            Flash::success('Portfolio Report created and queued for sending.');
        } catch (\Exception $e) {
            report($e);
            Flash::error($e->getMessage());
        }

        return redirect(route('portfolioReports.index'));
    }

    public function update($id, UpdatePortfolioReportRequest $request)
    {
        $portfolioReport = $this->portfolioReportRepository->find($id);

        if (empty($portfolioReport)) {
            Flash::error('Portfolio Report not found');

            return redirect(route('portfolioReports.index'));
        }

        $portfolioReport = $this->portfolioReportRepository->update($request->all(), $id);
        SendPortfolioReport::dispatch($portfolioReport);

        Flash::success('Portfolio Report updated and queued for sending.');

        return redirect(route('portfolioReports.index'));
    }
}
