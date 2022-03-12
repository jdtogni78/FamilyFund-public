<?php

namespace App\Http\Controllers\WebV1;

use App\Charts\BarChart;
use App\Charts\DoughnutChart;
use App\Charts\LineChart;
use App\Repositories\FundRepository;
use Flash;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use Response;
use App\Http\Controllers\FundController;
use App\Http\Controllers\APIv1\FundAPIControllerExt;
use App\Http\Controllers\APIv1\PortfolioAPIControllerExt;
use App\Models\PerformanceTrait;
use App\Repositories\PortfolioRepository;
use Spatie\TemporaryDirectory\Exceptions\PathAlreadyExists;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class FundControllerExt extends FundController
{
    use PerformanceTrait;
    use ChartBaseTrait;
    private FundPDF $fundPDF;

    public function __construct(FundRepository $fundRepo)
    {
        parent::__construct($fundRepo);
        $this->fundPDF = new FundPDF($this, false);
    }

    /**
     * Display the specified Fund.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        return $this->showAsOf($id, null);
    }

    /**
     * Display the specified Fund.
     *
     * @param int $id
     *
     * @return Response
     */
    public function showAsOf($id, $asOf=null, $isAdmin=false)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $arr = $this->createFundViewData($fund, $asOf, $isAdmin);

        return view('funds.show_ext')
            ->with('api', $arr);
    }

    /**
     * Display the specified Fund.
     * @param int $id
     * @return Response
     * @throws PathAlreadyExists
     */
    public function showPDFAsOf($id, $asOf=null, $isAdmin=false)
    {
        $debug_html = false;
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $arr = $this->createFundViewData($fund, $asOf, $isAdmin);
        $pdf = new FundPDF($arr, $isAdmin, $debug_html);

        return $pdf->inline('fund.pdf');
    }

    public function createFundViewData($fund, $asOf, $isAdmin = false) {
        if ($asOf == null) $asOf = date('Y-m-d');

        $api = new FundAPIControllerExt($this->fundRepository);
        $arr = $api->createFundResponse($fund, $asOf);
        $arr['monthly_performance'] = $api->createMonthlyPerformanceResponse($asOf);
        $arr['yearly_performance'] = $api->createYearlyPerformanceResponse($asOf);
        if ($isAdmin) {
            $arr['balances'] = $api->createAccountBalancesResponse($fund, $asOf);
        }

        $portController = new PortfolioAPIControllerExt(\App::make(PortfolioRepository::class));
        $portfolio = $fund->portfolios()->first();
        $arr['portfolio'] = $portController->createPortfolioResponse($portfolio, $asOf);

        $arr['as_of'] = $asOf;
        return $arr;
    }

}
