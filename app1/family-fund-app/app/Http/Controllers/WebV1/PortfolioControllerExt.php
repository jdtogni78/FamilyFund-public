<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\Traits\PortfolioRebalancePDF;
use App\Http\Controllers\Traits\VerboseTrait;
use App\Http\Resources\PortfolioAssetResource;
use App\Models\AssetExt;
use App\Models\PortfolioExt;
use App\Models\TradePortfolioItem;
use App\Repositories\PortfolioRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Laracasts\Flash\Flash;

class PortfolioControllerExt extends PortfolioController
{
    use VerboseTrait;

    public function __construct(PortfolioRepository $portfolioRepo)
    {
        parent::__construct($portfolioRepo);
    }

    public function showRebalancePDF($id, $start = null, $end = null)
    {
        /** @var PortfolioExt $portfolio */
        $portfolio = $this->portfolioRepository->find($id);

        if (empty($portfolio)) {
            Flash::error('Portfolio not found');
            return redirect(route('portfolios.index'));
        }

        if ($start === null) {
            $start = Carbon::now()->subMonths(3);
        } else {
            $start = Carbon::parse($start);
        }
        if ($end === null) {
            $end = Carbon::now();
        } else {
            $end = Carbon::parse($end);
        }

        $api = $this->createRebalanceResponse($portfolio, $start, $end);
        $api['asOf'] = $start;
        $api['endDate'] = $end;

        $pdf = new PortfolioRebalancePDF($api, false);

        return $pdf->inline('portfolio_rebalance.pdf');
    }

    public function showRebalance($id, $start = null, $end = null)
    {
        /** @var PortfolioExt $portfolio */
        $portfolio = $this->portfolioRepository->find($id);

        if (empty($portfolio)) {
            Flash::error('Portfolio not found');
            return redirect(route('portfolios.index'));
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

        $api = $this->createRebalanceResponse($portfolio, $start, $end);
        $api['asOf'] = $start;
        $api['endDate'] = $end;

        return view('portfolios.show_rebalance')->with('api', $api);
    }

    public function createRebalanceResponse(PortfolioExt $portfolio, Carbon $start, Carbon $end)
    {
        // Get all trade portfolios that overlap with the date range
        $tradePortfolios = $portfolio->tradePortfoliosBetween($start, $end);

        if ($tradePortfolios->isEmpty()) {
            return [
                'portfolio' => $portfolio,
                'tradePortfolios' => $tradePortfolios,
                'portfolioAssets' => new Collection(),
                'rebalance' => [],
                'symbols' => [],
            ];
        }

        // Preload all trade portfolio items
        $tradePortfolios->load('tradePortfolioItems');

        // Collect all unique symbols across all trade portfolios
        $allSymbols = [];
        foreach ($tradePortfolios as $tp) {
            foreach ($tp->tradePortfolioItems as $item) {
                $allSymbols[$item->symbol] = [
                    'symbol' => $item->symbol,
                    'type' => $item->type,
                ];
            }
        }

        /** @var Carbon $date */
        $date = $start->copy();
        $assets = [];
        $portfolioAssets = new Collection();
        $all = [];

        for (; $date->lt($end); $date->addDay()) {
            $this->debug("date: " . $date->toDateString());

            // Find active trade portfolio for this date
            $activeTP = $tradePortfolios->first(function ($tp) use ($date) {
                $tpStart = Carbon::parse($tp->start_dt);
                $tpEnd = Carbon::parse($tp->end_dt);
                return $date->gte($tpStart) && $date->lt($tpEnd);
            });

            if (!$activeTP) {
                continue;
            }

            $data = [];
            $port_value = $portfolio->valueAsOf($date);
            $this->debug("port_value: $port_value");

            if ($port_value <= 0) {
                continue;
            }

            $pas = $portfolio->assetsAsOf($date);
            $this->debug("pas count: " . $pas->count());

            if ($pas->count() == 0) {
                continue;
            }

            foreach ($pas as $pa) {
                $portfolioAssets->add(new PortfolioAssetResource($pa));
            }

            /** @var TradePortfolioItem $item */
            foreach ($activeTP->tradePortfolioItems as $item) {
                // Get asset from cache or lookup
                if (array_key_exists($item->symbol, $assets)) {
                    $asset = $assets[$item->symbol];
                } else {
                    $asset = AssetExt::getAsset($item->symbol, $item->type);
                    $assets[$item->symbol] = $asset;
                }

                if (!$asset) {
                    continue;
                }

                $this->debug("asset: $asset");

                // Find asset in portfolio assets
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
                if ($ap->count() < 1) {
                    continue;
                }

                $price = $ap->first()->price;
                $value = $shares * $price;
                $perc = $value / $port_value;

                $data[$item->symbol] = [
                    'value' => $value,
                    'perc' => $perc,
                    'max' => $item->target_share + $item->deviation_trigger,
                    'min' => $item->target_share - $item->deviation_trigger,
                    'target' => (float) $item->target_share,
                    'trade_portfolio_id' => $activeTP->id,
                ];
                $this->debug(json_encode($data[$item->symbol]));
            }

            $data['port_value'] = $port_value;
            $data['trade_portfolio_id'] = $activeTP->id;
            $all[$date->toDateString()] = $data;
        }

        $portfolioAssets = $portfolioAssets->unique('id')
            ->sortBy('start_dt')
            ->sortBy('asset_id');

        // Annotate trade portfolios with their items for the view
        foreach ($tradePortfolios as $tp) {
            $tp->items = $tp->tradePortfolioItems;
        }

        $api = [
            'portfolio' => $portfolio,
            'tradePortfolios' => $tradePortfolios,
            'portfolioAssets' => $portfolioAssets,
            'rebalance' => $all,
            'symbols' => array_values($allSymbols),
        ];

        return $api;
    }
}
