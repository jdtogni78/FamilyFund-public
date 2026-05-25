<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\ChartBaseTrait;
use App\Http\Controllers\Traits\FundPDF;
use App\Http\Controllers\Traits\FundSetupTrait;
use App\Http\Controllers\Traits\OverviewTrait;
use App\Http\Requests\CreateFundRequest;
use App\Http\Requests\CreateFundWithSetupRequest;
use App\Http\Requests\UpdateFundRequest;
use App\Models\FundExt;
use App\Repositories\FundRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Mockery\Exception;
use Response;
use App\Http\Controllers\Traits\FundTrait;
use Spatie\TemporaryDirectory\Exceptions\PathAlreadyExists;

class FundControllerExt extends AppBaseController
{
    use FundTrait;
    use ChartBaseTrait;
    use OverviewTrait;
    use FundSetupTrait;

    /** @var  FundRepository */
    protected $fundRepository;

    /** @var  TransactionRepository */
    protected $transactionRepository;

    public function __construct(FundRepository $fundRepo, TransactionRepository $transactionRepo)
    {
        $this->fundRepository = $fundRepo;
        $this->transactionRepository = $transactionRepo;
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

        return view('funds.withdrawal_goal_edit')
            ->with('fund', $fund);
    }

    /**
     * Update withdrawal rule goal for a fund.
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
            'withdrawal_yearly_expenses' => 'nullable|numeric|min:0',
            'withdrawal_net_worth_pct' => 'nullable|numeric|min:1|max:100',
            'withdrawal_rate' => 'nullable|numeric|min:0.5|max:10',
            'expected_growth_rate' => 'nullable|numeric|min:0.5|max:20',
            'independence_mode' => 'nullable|in:perpetual,countdown',
            'independence_target_date' => 'nullable|date|after:today',
        ]);

        $fund->update([
            'withdrawal_yearly_expenses' => $request->withdrawal_yearly_expenses ?: null,
            'withdrawal_net_worth_pct' => $request->withdrawal_net_worth_pct ?: 100,
            'withdrawal_rate' => $request->withdrawal_rate ?: 4,
            'expected_growth_rate' => $request->expected_growth_rate ?: 7,
            'independence_mode' => $request->independence_mode ?? 'perpetual',
            'independence_target_date' => $request->independence_mode === 'countdown'
                ? $request->independence_target_date
                : null,  // Clear date when switching to perpetual
        ]);

        Flash::success('Withdrawal Rule Goal updated successfully.');
        return redirect(route('funds.show', $id));
    }

    // --- inlined from former base ---

    public function index(Request $request)
    {
        $this->authorize('viewAny', FundExt::class);

        $funds = $this->fundRepository->withAuthorization()->all();

        return view('funds.index')
            ->with('funds', $funds);
    }

    public function create()
    {
        $this->authorize('create', FundExt::class);

        return view('funds.create');
    }

    public function store(CreateFundRequest $request)
    {
        $this->authorize('create', FundExt::class);

        $input = $request->all();

        $fund = $this->fundRepository->create($input);

        Flash::success('Fund saved successfully.');

        return redirect(route('funds.index'));
    }

    public function edit($id)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');

            return redirect(route('funds.index'));
        }

        $this->authorize('update', $fund);

        return view('funds.edit')->with('fund', $fund);
    }

    public function update($id, UpdateFundRequest $request)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');

            return redirect(route('funds.index'));
        }

        $this->authorize('update', $fund);

        $fund = $this->fundRepository->update($request->all(), $id);

        Flash::success('Fund updated successfully.');

        return redirect(route('funds.index'));
    }

    public function destroy($id)
    {
        $fund = $this->fundRepository->find($id);

        if (empty($fund)) {
            Flash::error('Fund not found');

            return redirect(route('funds.index'));
        }

        $this->authorize('delete', $fund);

        $this->fundRepository->delete($id);

        Flash::success('Fund deleted successfully.');

        return redirect(route('funds.index'));
    }

    public function createWithSetup()
    {
        $this->authorize('create', FundExt::class);

        return view('funds.create_with_setup');
    }

    public function storeWithSetup(CreateFundWithSetupRequest $request)
    {
        $this->authorize('create', FundExt::class);

        $input = $request->all();
        $isPreview = $request->input('preview', false);

        try {
            $setupData = $this->setupFund($input, $isPreview);

            if ($isPreview) {
                return view('funds.preview_setup', [
                    'preview' => $setupData,
                    'input' => $input,
                ]);
            } else {
                Flash::success('Fund created successfully with account, portfolio, and initial transaction!');
                return redirect(route('funds.show', $setupData['fund']->id));
            }
        } catch (\Exception $e) {
            Flash::error('Fund creation failed: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
}
