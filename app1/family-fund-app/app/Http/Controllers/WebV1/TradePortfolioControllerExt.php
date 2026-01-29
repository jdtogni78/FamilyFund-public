<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\Traits\VerboseTrait;
use App\Http\Controllers\Traits\MailTrait;
use App\Http\Controllers\Traits\CashDepositTrait;

use App\Http\Requests\RebalanceTradePortfolioRequest;
use App\Http\Resources\PortfolioAssetResource;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use App\Models\PortfolioAsset;
use App\Models\TradePortfolioExt;
use App\Models\TradePortfolioItem;
use App\Models\TradePortfolioItemExt;
use App\Repositories\TradePortfolioRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;
use Mockery\Exception;
use Response;
use App\Http\Controllers\TradePortfolioController;
use Symfony\Component\HttpFoundation\Request;
use App\Mail\TradePortfolioAnnouncementMail;
use Illuminate\Support\MessageBag;
use App\Models\CashDepositExt;
use App\Models\AccountExt;
use App\Models\TransactionExt;
use App\Http\Controllers\Traits\TransactionTrait;

class TradePortfolioControllerExt extends TradePortfolioController
{
    use VerboseTrait, MailTrait, CashDepositTrait, TransactionTrait;

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
     * Show the rebalance edit form for modifying portfolio settings and items.
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\View
     */
    public function rebalance($id)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            Flash::error('Trade Portfolio not found');
            return redirect(route('tradePortfolios.index'));
        }

        $tradePortfolio->annotateAssetsAndGroups();
        $tradePortfolio->annotateTotalShares();

        $date = new Carbon();
        $api = [
            'assetMap' => AssetExt::symbolMap(),
            'typeMap' => TradePortfolioItemExt::typeMap(),
            'tradePortfolio' => $tradePortfolio,
            'items' => $tradePortfolio->tradePortfolioItems()->get(),
            'start_dt' => $date->format('Y-m-d'),
            'end_dt' => '9999-12-31',
        ];

        return view('trade_portfolios.rebalance', $api);
    }

    /**
     * Process the rebalance form submission.
     * Creates a new trade portfolio version with modified settings and items.
     *
     * @param RebalanceTradePortfolioRequest $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function doRebalance(RebalanceTradePortfolioRequest $request, $id)
    {
        $newTP = null;

        try {
            DB::transaction(function () use ($request, $id, &$newTP) {
                /** @var TradePortfolioExt $tradePortfolio */
                $tradePortfolio = $this->tradePortfolioRepository->find($id);

                if (empty($tradePortfolio)) {
                    throw new \Exception('Trade Portfolio not found');
                }

                $settings = [
                    'cash_target' => $request->input('cash_target'),
                    'cash_reserve_target' => $request->input('cash_reserve_target'),
                    'rebalance_period' => $request->input('rebalance_period'),
                    'mode' => $request->input('mode'),
                    'minimum_order' => $request->input('minimum_order'),
                    'max_single_order' => $request->input('max_single_order'),
                ];

                $items = $request->input('items', []);

                $newTP = $tradePortfolio->rebalanceWithItems(
                    $request->input('start_dt'),
                    $request->input('end_dt'),
                    $settings,
                    $items
                );
            });

            Flash::success('Portfolio rebalanced successfully');
            return redirect(route('tradePortfolios.show', $newTP->id));
        } catch (\Exception $e) {
            Flash::error('Failed to rebalance portfolio: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
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
            'portfolio' => $tradePortfolio->portfolio,
        ];

        $api['api'] = $api;
        return $api;
    }

    // create a view to compare the old and new trade portfolios
    public function showDiff($id)
    {
        $api = $this->createDiffAPIResponse($id);
        return view('trade_portfolios.show_diff')->with('api', $api);
    }

    public function createDiffAPIResponse($id)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);
        // find the previous trade portfolio (by date)
        $prevTP = $tradePortfolio->previous();
        $api = [
            'old' => $prevTP,
            'new' => $tradePortfolio,
        ];
        $api['old']['items'] = $prevTP->tradePortfolioItems()->get();
        $api['new']['items'] = $tradePortfolio->tradePortfolioItems()->get();
        $api['old']->annotateTotalShares();
        $api['new']->annotateTotalShares();
        $api['old']['portfolio'] = $prevTP->portfolio;
        $api['new']['portfolio'] = $tradePortfolio->portfolio;
        return $api;
    }

    public function announce($id)
    {
        $api = $this->createDiffAPIResponse($id);
        $email = new TradePortfolioAnnouncementMail($api);
        $to = $api['new']->portfolio->fund()->first()->fundAccount()->email_cc;
        $this->sendMail($email, $to);
        return redirect(route('tradePortfolios.show', $api['new']->id));
    }

    // TODO - May be deprecaded? Needs corrections...
    public function createRebalanceResponse(TradePortfolioExt $tradePortfolio, Carbon $start, Carbon $end)
    {
        $items = $tradePortfolio->tradePortfolioItems()->get();
        /** @var Carbon $date */
        $date = $start->copy();
        $port = $tradePortfolio->portfolio;
        $assets = [];
        $porfolioAssets = new Collection();
        $all = [];

        for (; $date->lt($end); $date->addDay()) {
            $this->debug("date: " . $date->toDateString());
            $data = [];
            $port_value = $tradePortfolio->portfolio->valueAsOf($date);
            $this->debug("port_value: $port_value");

            $pas = $port->assetsAsOf($date);
            $this->debug("pas: $pas");
            if ($pas->count() == 0) {
                continue;
            }
            foreach ($pas as $pa) {
                $porfolioAssets->add(new PortfolioAssetResource($pa));
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

        $porfolioAssets = $porfolioAssets->unique('id')
            ->sortBy('start_dt')
            ->sortBy('asset_id');
        $tradePortfolio->items = $tradePortfolio->tradePortfolioItems()->get();
        $api = [
            'tradePortfolio' => $tradePortfolio,
            'portfolio' => $tradePortfolio->portfolio,
            'portfolioAssets' => $porfolioAssets,
            'rebalance' => $all,
        ];

        $api['api'] = $api;
        return $api;
    }

    public function previewCashDeposits($id)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);
        $data = $this->executeCashDeposits($tradePortfolio, true);
        $errors = $data['errors'];
        $api = [
            'accountMap' => AccountExt::accountMap(),
        ];

        return view('cash_deposits.preview')
            ->with('data', $data)
            ->with('api', $api)
            ->with('tradePortfolio', $tradePortfolio)
            ->withErrors(new MessageBag($errors));
    }
    
    public function doCashDeposits($id)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);
        $ret = $this->executeCashDeposits($tradePortfolio, false);
        $errors = $ret['errors'];
        return redirect(route('tradePortfolios.index'))
            ->withErrors(new MessageBag($errors));
    }

}
