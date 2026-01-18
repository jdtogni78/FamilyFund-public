<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AssetPriceController;
use App\Http\Controllers\Traits\DetectsDataIssuesTrait;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use App\Models\PortfolioAsset;
use App\Repositories\AssetPriceRepository;
use Illuminate\Http\Request;

class AssetPriceControllerExt extends AssetPriceController
{
    use DetectsDataIssuesTrait;
    public function __construct(AssetPriceRepository $assetPriceRepo)
    {
        parent::__construct($assetPriceRepo);
    }

    /**
     * Display a listing of AssetPrices with filtering and pagination.
     */
    public function index(Request $request)
    {
        $query = AssetPrice::with('asset');

        $fundId = $request->input('fund_id');
        $assetIds = $request->input('asset_id', []);

        // Normalize asset_id to array and filter out 'none'
        if (!is_array($assetIds)) {
            $assetIds = $assetIds ? [$assetIds] : [];
        }
        $assetIds = array_filter($assetIds, fn($id) => $id && $id !== 'none');

        // Filter by fund (through assets in fund's portfolios)
        if ($fundId && $fundId !== 'none') {
            $fundAssetIds = PortfolioAsset::whereHas('portfolio', function ($q) use ($fundId) {
                $q->where('fund_id', $fundId);
            })->distinct()->pluck('asset_id')->toArray();

            $query->whereIn('asset_id', $fundAssetIds);
        }

        // Filter by asset(s)
        if (!empty($assetIds)) {
            if (count($assetIds) === 1) {
                $query->where('asset_id', $assetIds[0]);
            } else {
                $query->whereIn('asset_id', $assetIds);
            }
        }

        // Filter by date range (default to -1 year if no start date provided)
        $startDt = $request->input('start_dt');
        $endDt = $request->input('end_dt');

        // Default to -1 year if no start date explicitly provided
        if (!$startDt) {
            $startDt = now()->subYear()->format('Y-m-d');
        }

        if ($startDt) {
            $query->where('start_dt', '>=', $startDt);
        }
        if ($endDt) {
            $query->where('start_dt', '<=', $endDt);
        }

        // Join for sorting by asset name/type
        $query->join('assets', 'asset_prices.asset_id', '=', 'assets.id')
            ->select('asset_prices.*');

        // Dynamic sorting
        $sortColumn = $request->input('sort', 'start_dt');
        $sortDir = $request->input('dir', 'desc');
        $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc';

        switch ($sortColumn) {
            case 'asset':
                $query->orderBy('assets.name', $sortDir);
                break;
            case 'type':
                $query->orderBy('assets.type', $sortDir);
                break;
            case 'price':
                $query->orderBy('asset_prices.price', $sortDir);
                break;
            case 'end_dt':
                $query->orderBy('asset_prices.end_dt', $sortDir);
                break;
            case 'start_dt':
            default:
                $query->orderBy('asset_prices.start_dt', $sortDir);
                break;
        }

        // Get ALL records for gap detection (clone query to avoid affecting pagination)
        $allFilteredRecords = clone $query;
        $allFilteredRecords = $allFilteredRecords->get();

        // Now paginate for display
        $assetPrices = $query->paginate(50);

        // Prepare chart data
        $chartData = null;
        if (!empty($assetIds)) {
            if (count($assetIds) === 1) {
                // Single asset selected - show single line chart
                $chartData = $this->getChartData($assetIds[0], $startDt, $endDt);
            } elseif (count($assetIds) <= 8) {
                // Multiple assets selected (up to 8) - show multi-line chart
                $chartData = $this->getMultiAssetChartData($assetIds, $startDt, $endDt);
            }
        } elseif ($fundId && $fundId !== 'none') {
            // Fund selected but no specific asset - show multi-asset chart if <= 8 assets
            $chartData = $this->getFundAssetsChartData($fundId, $startDt, $endDt);
        }

        // Get asset map - filtered by fund if selected
        $assetMap = $this->getAssetMapWithType($fundId);

        // Detect data issues (overlaps and gaps) on ALL filtered records, not just current page
        $dataWarnings = $this->detectDataIssues($allFilteredRecords, 'asset_id', 'asset');

        $api = [
            'assetMap' => $assetMap,
            'fundMap' => $this->getFundMap(),
            'filters' => array_merge(
                $request->only(['asset_id', 'fund_id', 'end_dt']),
                ['start_dt' => $startDt] // Use the actual start_dt being applied (with default)
            ),
        ];

        // Collect issue dates for chart visualization
        $issueDates = $this->collectIssueDates($dataWarnings);

        return view('asset_prices.index')
            ->with('assetPrices', $assetPrices)
            ->with('api', $api)
            ->with('chartData', $chartData)
            ->with('dataWarnings', $dataWarnings)
            ->with('issueDates', $issueDates);
    }

    /**
     * Get chart data for a specific asset's price history.
     */
    protected function getChartData($assetId, $startDt = null, $endDt = null)
    {
        $query = AssetPrice::where('asset_id', $assetId)
            ->orderBy('start_dt');

        if ($startDt) {
            $query->where('start_dt', '>=', $startDt);
        }
        if ($endDt) {
            $query->where('start_dt', '<=', $endDt);
        }

        $prices = $query->get();

        if ($prices->isEmpty()) {
            return null;
        }

        $asset = AssetExt::find($assetId);

        return [
            'labels' => $prices->pluck('start_dt')->map(fn($d) => $d->format('Y-m-d'))->toArray(),
            'data' => $prices->pluck('price')->map(fn($p) => (float) $p)->toArray(),
            'assetName' => $asset->name ?? 'Unknown',
        ];
    }

    /**
     * Get chart data for multiple selected assets.
     */
    protected function getMultiAssetChartData($assetIds, $startDt = null, $endDt = null)
    {
        $assets = AssetExt::whereIn('id', $assetIds)->orderBy('name')->get();

        if ($assets->isEmpty()) {
            return null;
        }

        $datasets = [];
        $allDates = collect();

        $colors = [
            '#16a34a', '#2563eb', '#dc2626', '#9333ea',
            '#ea580c', '#0891b2', '#4f46e5', '#be185d'
        ];

        foreach ($assets as $index => $asset) {
            $priceQuery = AssetPrice::where('asset_id', $asset->id)
                ->orderBy('start_dt');

            if ($startDt) {
                $priceQuery->where('start_dt', '>=', $startDt);
            }
            if ($endDt) {
                $priceQuery->where('start_dt', '<=', $endDt);
            }

            $prices = $priceQuery->get();

            foreach ($prices as $price) {
                $allDates->push($price->start_dt->format('Y-m-d'));
            }

            $color = $colors[$index % count($colors)];
            $datasets[] = [
                'label' => $asset->name,
                'data' => $prices->map(fn($p) => [
                    'x' => $p->start_dt->format('Y-m-d'),
                    'y' => (float) $p->price
                ])->toArray(),
                'borderColor' => $color,
                'backgroundColor' => $color . '20',
                'fill' => false,
                'tension' => 0.1,
                'pointRadius' => 2,
            ];
        }

        $allDates = $allDates->unique()->sort()->values()->toArray();

        return [
            'labels' => $allDates,
            'datasets' => $datasets,
            'multiAsset' => true,
        ];
    }

    /**
     * Get chart data for all assets in a fund (when no specific asset selected).
     * Only returns data if there are 8 or fewer unique assets.
     */
    protected function getFundAssetsChartData($fundId, $startDt = null, $endDt = null)
    {
        // Get unique asset IDs for this fund
        $assetIds = PortfolioAsset::whereHas('portfolio', function ($q) use ($fundId) {
            $q->where('fund_id', $fundId);
        })->distinct()->pluck('asset_id');

        // Only show chart if 8 or fewer assets
        if ($assetIds->count() > 8 || $assetIds->isEmpty()) {
            return null;
        }

        return $this->getMultiAssetChartData($assetIds->toArray(), $startDt, $endDt);
    }

    /**
     * Get asset map with type information for dropdowns.
     * Optionally filtered by fund.
     */
    protected function getAssetMapWithType($fundId = null)
    {
        $map = ['none' => 'Select Asset'];

        if ($fundId && $fundId !== 'none') {
            // Get assets that belong to portfolios of this fund
            $assetIds = PortfolioAsset::whereHas('portfolio', function ($q) use ($fundId) {
                $q->where('fund_id', $fundId);
            })->distinct()->pluck('asset_id');

            $assets = AssetExt::whereIn('id', $assetIds)->orderBy('name')->get();
        } else {
            $assets = AssetExt::orderBy('name')->get();
        }

        foreach ($assets as $asset) {
            $map[$asset->id] = $asset->name . ' (' . $asset->type . ')';
        }
        return $map;
    }

    /**
     * Get fund map for dropdown.
     */
    protected function getFundMap()
    {
        $funds = \App\Models\Fund::orderBy('name')->get();
        $map = ['none' => 'Select Fund'];
        foreach ($funds as $fund) {
            $map[$fund->id] = $fund->name;
        }
        return $map;
    }

    /**
     * Show the form for creating a new AssetPrice.
     */
    public function create()
    {
        $api = [
            'assetMap' => $this->getAssetMapWithType(),
        ];

        return view('asset_prices.create')->with('api', $api);
    }

    /**
     * Show the form for editing the specified AssetPrice.
     */
    public function edit($id)
    {
        $assetPrice = $this->assetPriceRepository->find($id);

        if (empty($assetPrice)) {
            \Flash::error('Asset Price not found');
            return redirect(route('assetPrices.index'));
        }

        $api = [
            'assetMap' => $this->getAssetMapWithType(),
        ];

        return view('asset_prices.edit')
            ->with('assetPrice', $assetPrice)
            ->with('api', $api);
    }
}
