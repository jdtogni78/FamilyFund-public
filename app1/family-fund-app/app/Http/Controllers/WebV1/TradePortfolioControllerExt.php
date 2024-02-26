<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\SplitTradePortfolioRequest;
use App\Models\AssetExt;
use App\Models\TradePortfolioExt;
use App\Repositories\TradePortfolioRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;
use Mockery\Exception;
use Response;
use App\Http\Controllers\TradePortfolioController;
use Symfony\Component\HttpFoundation\Request;

class TradePortfolioControllerExt extends TradePortfolioController
{
    public function __construct(TradePortfolioRepository $tradePortfolioRepo)
    {
        parent::__construct($tradePortfolioRepo);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $tradePortfolios = $this->tradePortfolioRepository->all()
            ->sortByDesc('end_dt');

        return view('trade_portfolios.index')
            ->with('tradePortfolios', $tradePortfolios);
    }

    public function show($id)
    {
        return $this->showAsOf($id, null);
    }

    public function createWithParams(Request $request)
    {
        $portfolio_id = $request->input('portfolio_id');
        return parent::create()->with('portfolio_id', $portfolio_id);
    }

    public function split($id)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            Flash::error('Trade Portfolio not found');
            return redirect(route('tradePortfolios.index'));
        }

        $api = $this->createAPIResponse($tradePortfolio);
        $date = new Carbon();
        $api['api']['tradePortfolio']['start_dt'] = $date;
        $api['api']['tradePortfolio']['show_end_dt'] = $date;
        $api['api']['tradePortfolio']['end_dt'] = new Carbon('9999-12-31');

        return view('trade_portfolios.show', $api)
            ->with('split', true);
    }

    public function doSplit(SplitTradePortfolioRequest $request)
    {
        // create db transaction
        DB::transaction(function () use ($request, &$newTP) {
            $id = $request->route('id');
            $start_dt = $request->input('start_dt');
            $end_dt = $request->input('end_dt');
            Log::debug("doSplit id: $id, start_dt: $start_dt, end_dt: $end_dt");
            /** @var TradePortfolioExt $tradePortfolio */
            $tradePortfolio = $this->tradePortfolioRepository->find($id);
            $newTP = $tradePortfolio->splitWithItems($start_dt, $end_dt);
        });
        return redirect(route('tradePortfolios.show', $newTP->id));
    }

    /**
     * Display the specified Fund.
     *
     * @param int $id
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function showAsOf($id, $asOf=null)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            Flash::error('Trade Portfolio not found');
            return redirect(route('tradePortfolios.index'));
        }

        $api = $this->createAPIResponse($tradePortfolio);
        if ($asOf === null) {
            $asOf = Carbon::now();
        } else {
            $asOf = Carbon::parse($asOf);
        }

        $api['asOf'] = $asOf;
        return view('trade_portfolios.show', $api);
    }

    public function createAPIResponse(TradePortfolioExt $tradePortfolio)
    {
        Log::info($tradePortfolio->tradePortfolioItems()->count());
        $tradePortfolio->annotateAssetsAndGroups();
        $tradePortfolio->annotateTotalShares();

        $api = [
            'tradePortfolio' => $tradePortfolio,
            'portfolio' => $tradePortfolio->portfolio(),
        ];

        $api['api'] = $api;
        return $api;
    }
}
