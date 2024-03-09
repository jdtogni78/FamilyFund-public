<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\Traits\VerboseTrait;
use App\Http\Requests\SplitTradePortfolioRequest;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use App\Models\PortfolioAsset;
use App\Models\TradePortfolioExt;
use App\Models\TradePortfolioItem;
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
    use VerboseTrait;

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
            ->with('split', true)
            ->with('asOf', $date);
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

    public function showRebalance($id, $start=null, $end=null)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            Flash::error('Trade Portfolio not found');
            return redirect(route('tradePortfolios.index'));
        }

        if ($start === null) {
            $start = Carbon::now()->subDays(30);
        } else {
            $start = Carbon::parse($start);
        }
        if ($end === null) {
            $end = Carbon::now();
        } else {
            $end = Carbon::parse($end);
        }
        $api = $this->createRebalanceResponse($tradePortfolio, $start, $end);

        $api['asOf'] = $start;
        return view('trade_portfolios.show_rebalance')->with('api', $api);
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

    public function createRebalanceResponse(TradePortfolioExt $tradePortfolio, Carbon $start, Carbon $end)
    {
        $items = $tradePortfolio->tradePortfolioItems()->get();
        /** @var Carbon $date */
        $date = $start->copy();
        $port = $tradePortfolio->portfolio();
        $assets = [];

        for (; $date->lt($end); $date->addDay()) {
            $this->debug("date: " . $date->toDateString());
            $data = [];
            $port_value = $tradePortfolio->portfolio()->valueAsOf($date);
            $this->debug("port_value: $port_value");

            $pas = $port->assetsAsOf($date);
            $this->debug("pas: $pas");
            if ($pas->count() == 0) {
                continue;
            }

            /** @var TradePortfolioItem $item */
            foreach ($items as $item) {
                // get asset from assets using symbol
                if (array_key_exists($item->symbol, $assets)) {
                    $asset = $assets[$item->symbol];
                } else {
                    $asset = AssetExt::getAsset($item->symbol, $item->type);
                    $assets[$item->symbol] = $asset;
                }

                $this->debug("asset: $asset");
                // find asset in pas using id
                $pa = null;
                foreach ($pas as $p) {
                    if ($p->asset_id == $asset->id) {
                        $pa = $p;
                        break;
                    }
                }
                if ($pa == null) {
                    continue;
                }
                $shares = $pa->position;
                $ap = $asset->priceAsOf($date);
                if ($ap->count() < 1) continue;
                $price = $ap->first()->price;
                $value = $shares * $price;
                $perc = $value/$port_value;

                $data[$item->symbol] = [
                    'value' => $value,
                    'perc' => $perc,
                    'max' => $item->target_share + $item->deviation_trigger,
                    'min' => $item->target_share - $item->deviation_trigger,
                    'target' => 0 + $item->target_share,
                ];
                $this->debug(json_encode($data[$item->symbol]));
            }
            $data['port_value'] = $port_value;
            $all[$date->toDateString()] = $data;
        }

//        // delete all items where data is same before and after
//        $date = $start->copy();
//        for (; $date->lt($end); $date->addDay()) {
//            $prevdt = $date->copy()->subDay();
//            $nextdt = $date->copy()->addDay();
//            if (array_key_exists($prevdt->toDateString(), $all) &&
//                array_key_exists($nextdt->toDateString(), $all)) {
//                $prev = $all[$prevdt->toDateString()];
//                $next = $all[$nextdt->toDateString()];
//                $same = true;
//                foreach ($prev as $key => $value) {
//                    if ($key == 'port_value') continue;
//                    if ($prev[$key] != $next[$key]) {
//                        $same = false;
//                        break;
//                    }
//                }
//                if ($same) {
//                    unset($all[$date->toDateString()]);
//                }
//            }
//        }

        $tradePortfolio->items = $tradePortfolio->tradePortfolioItems()->get();
        $api = [
            'tradePortfolio' => $tradePortfolio,
            'portfolio' => $tradePortfolio->portfolio(),
            'rebalance' => $all,
        ];

        $api['api'] = $api;
        return $api;
    }
}
