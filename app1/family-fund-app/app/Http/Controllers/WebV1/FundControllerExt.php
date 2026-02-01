<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\Traits\ChartBaseTrait;
use App\Http\Controllers\Traits\FundPDF;
use App\Http\Controllers\Traits\OverviewTrait;
use App\Models\FundExt;
use App\Repositories\FundRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Mockery\Exception;
use Response;
use App\Http\Controllers\FundController;
use App\Http\Controllers\Traits\FundTrait;
use Spatie\TemporaryDirectory\Exceptions\PathAlreadyExists;

class FundControllerExt extends FundController
{
    use FundTrait;
    use ChartBaseTrait;
    use OverviewTrait;

    public function __construct(FundRepository $fundRepo, TransactionRepository $transactionRepo)
    {
        parent::__construct($fundRepo, $transactionRepo);
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
    public function showAsOf($id, $asOf=null)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $this->authorize('view', $fund);

        $arr = $this->createFullFundResponse($fund, $asOf, $this->isAdmin());

        return view('funds.show_ext')
            ->with('api', $arr)
            ->with('asOf', $arr['asOf']);
    }

    /**
     * Display the specified Fund.
     * @param int $id
     * @return Response
     * @throws PathAlreadyExists
     */
    public function showPDFAsOf($id, $asOf=null)
    {
        $debug_html = false;
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $this->authorize('view', $fund);

        $isAdmin = $this->isAdmin();
        $arr = $this->createFullFundResponse($fund, $asOf, $isAdmin);
        $pdf = new FundPDF();
        $pdf->createFundPDF($arr, $isAdmin, $debug_html);

        return $pdf->inline('fund.pdf');
    }

    public function tradeBands($id)
    {
        return $this->tradeBandsAsOf($id, null);
    }
    
    /**
     * Display the specified Fund.
     *
     * @param int $id
     *
     * @return Response
     */
    public function tradeBandsAsOf($id, $asOf)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $this->authorize('view', $fund);

        $fromDate = request()->get('from');
        $arr = $this->createFundResponseTradeBands($fund, $asOf, $this->isAdmin(), $fromDate);

        return view('funds.show_trade_bands')
            ->with('api', $arr)
            ->with('asOf', $arr['asOf'])
            ->with('fromDate', $arr['fromDate']);
    }

    /**
     * @param int $id
     * @param string $asOf
     * @return Response
     * @throws PathAlreadyExists
     */
    public function showTradeBandsPDFAsOf($id, $asOf=null)
    {
        $debug_html = false;
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $this->authorize('view', $fund);

        $fromDate = request()->get('from');
        $isAdmin = $this->isAdmin();
        $arr = $this->createFundResponseTradeBands($fund, $asOf, $isAdmin, $fromDate);
        $pdf = new FundPDF();
        $pdf->createTradeBandsPDF($arr, $isAdmin, $debug_html);

        return $pdf->inline('fund.pdf');
    }

    /**
     * Display portfolios for a fund.
     *
     * @param int $id
     * @return Response
     */
    public function portfolios($id)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $this->authorize('view', $fund);

        $portfolios = $fund->portfolios()->get();

        return view('funds.portfolios')
            ->with('fund', $fund)
            ->with('portfolios', $portfolios);
    }

    /**
     * Display the fund overview (Monarch-inspired).
     *
     * @param int $id
     * @return Response
     */
    public function overview($id)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $this->authorize('view', $fund);

        $asOf = request()->get('as_of', date('Y-m-d'));
        $period = request()->get('period', '1Y');
        $groupBy = request()->get('group_by', 'category');

        // Validate period and groupBy
        if (!in_array(strtoupper($period), self::$validPeriods)) {
            $period = self::$defaultPeriod;
        }
        if (!in_array($groupBy, self::$validGroupBy)) {
            $groupBy = 'category';
        }

        $overviewData = $this->createFundOverviewResponse($fund, $asOf, $period, $groupBy);

        return view('funds.overview')
            ->with('api', $overviewData)
            ->with('asOf', $asOf)
            ->with('period', strtoupper($period))
            ->with('groupBy', $groupBy);
    }

    /**
     * Return overview data as JSON for AJAX updates.
     *
     * @param int $id
     * @return Response
     */
    public function overviewData($id)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            return response()->json(['error' => 'Fund not found'], 404);
        }

        $this->authorize('view', $fund);

        $asOf = request()->get('as_of', date('Y-m-d'));
        $period = request()->get('period', '1Y');
        $groupBy = request()->get('group_by', 'category');

        // Validate period and groupBy
        if (!in_array(strtoupper($period), self::$validPeriods)) {
            $period = self::$defaultPeriod;
        }
        if (!in_array($groupBy, self::$validGroupBy)) {
            $groupBy = 'category';
        }

        $overviewData = $this->createFundOverviewResponse($fund, $asOf, $period, $groupBy);

        return response()->json($overviewData);
    }

    /**
     * Show form to edit 4% rule goal for a fund.
     *
     * @param int $id
     * @return Response
     */
    public function editFourPctGoal($id)
    {
        $fund = FundExt::find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $this->authorize('update', $fund);

        return view('funds.four_pct_goal_edit')
            ->with('fund', $fund);
    }

    /**
     * Update 4% rule goal for a fund.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function updateFourPctGoal(Request $request, $id)
    {
        $fund = FundExt::find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');
            return redirect(route('funds.index'));
        }

        $this->authorize('update', $fund);

        $request->validate([
            'four_pct_yearly_expenses' => 'nullable|numeric|min:0',
            'four_pct_net_worth_pct' => 'nullable|numeric|min:1|max:100',
        ]);

        $fund->update([
            'four_pct_yearly_expenses' => $request->four_pct_yearly_expenses ?: null,
            'four_pct_net_worth_pct' => $request->four_pct_net_worth_pct ?: 100,
        ]);

        Flash::success('4% Rule Goal updated successfully.');
        return redirect(route('funds.show', $id));
    }
}
