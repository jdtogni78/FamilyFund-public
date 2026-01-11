<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\PortfolioAssetController;
use App\Http\Controllers\Traits\DetectsDataIssuesTrait;
use App\Models\AssetExt;
use App\Models\Portfolio;
use App\Models\PortfolioAsset;
use App\Repositories\PortfolioAssetRepository;
use Illuminate\Http\Request;

class PortfolioAssetControllerExt extends PortfolioAssetController
{
    use DetectsDataIssuesTrait;
    public function __construct(PortfolioAssetRepository $portfolioAssetRepo)
    {
        parent::__construct($portfolioAssetRepo);
    }

    /**
     * Display a listing of PortfolioAssets with filtering and pagination.
     */
    public function index(Request $request)
    {
        $query = PortfolioAsset::with(['asset', 'portfolio.fund']);

        $fundId = $request->input('fund_id');
        $assetIds = $request->input('asset_id', []);

        // Normalize asset_id to array and filter out 'none'
        if (!is_array($assetIds)) {
            $assetIds = $assetIds ? [$assetIds] : [];
        }
        $assetIds = array_filter($assetIds, fn($id) => $id && $id !== 'none');

        // Filter by fund (through portfolio)
        if ($fundId && $fundId !== 'none') {
            $query->whereHas('portfolio', function ($q) use ($fundId) {
                $q->where('fund_id', $fundId);
            });
        }

        // Filter by asset(s)
        if (!empty($assetIds)) {
            if (count($assetIds) === 1) {
                $query->where('asset_id', $assetIds[0]);
            } else {
                $query->whereIn('asset_id', $assetIds);
            }
        }

        // Filter by date range - show records active during the period
        // A record is active during [start_dt, end_dt] if: record.start_dt <= filter.end_dt AND record.end_dt >= filter.start_dt
        $startDt = $request->input('start_dt');
        $endDt = $request->input('end_dt');

        if (!empty($startDt)) {
            // Record must still be active on or after this date
            $query->where('end_dt', '>=', $startDt);
        }
        if (!empty($endDt)) {
            // Record must have started on or before this date
            $query->where('start_dt', '<=', $endDt);
        }

        // Join for sorting
        $query->join('assets', 'portfolio_assets.asset_id', '=', 'assets.id')
            ->join('portfolios', 'portfolio_assets.portfolio_id', '=', 'portfolios.id')
            ->join('funds', 'portfolios.fund_id', '=', 'funds.id')
            ->select('portfolio_assets.*');

        // Dynamic sorting
        $sortColumn = $request->input('sort', 'start_dt');
        $sortDir = $request->input('dir', 'desc');
        $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc';

        switch ($sortColumn) {
            case 'fund':
                $query->orderBy('funds.name', $sortDir);
                break;
            case 'asset':
                $query->orderBy('assets.name', $sortDir);
                break;
            case 'position':
                $query->orderBy('portfolio_assets.position', $sortDir);
                break;
            case 'end_dt':
                $query->orderBy('portfolio_assets.end_dt', $sortDir);
                break;
            case 'start_dt':
            default:
                $query->orderBy('portfolio_assets.start_dt', $sortDir);
                break;
        }

        $portfolioAssets = $query->paginate(50);

        // Prepare chart data
        $chartData = null;
        if (!empty($assetIds)) {
            if (count($assetIds) === 1) {
                // Single asset selected - show single line chart
                $chartData = $this->getChartData($assetIds[0], $fundId, $startDt, $endDt);
            } elseif (count($assetIds) <= 8) {
                // Multiple assets selected (up to 8) - show multi-line chart
                $chartData = $this->getSelectedAssetsChartData($assetIds, $fundId, $startDt, $endDt);
            }
        } elseif ($fundId && $fundId !== 'none') {
            // Fund selected but no specific asset - show multi-asset chart if <= 8 assets
            $chartData = $this->getMultiAssetChartData($fundId, $startDt, $endDt);
        }

        // Get asset map - filtered by fund if selected
        $assetMap = $this->getAssetMapWithType($fundId);

        // Detect data issues (overlaps and gaps)
        $dataWarnings = $this->detectDataIssues($portfolioAssets, 'asset_id', 'asset');

        $api = [
            'assetMap' => $assetMap,
            'fundMap' => $this->getFundMap(),
            'filters' => $request->only(['asset_id', 'fund_id', 'start_dt', 'end_dt']),
        ];

        // Collect issue dates for chart visualization
        $issueDates = $this->collectIssueDates($dataWarnings);

        return view('portfolio_assets.index')
            ->with('portfolioAssets', $portfolioAssets)
            ->with('api', $api)
            ->with('chartData', $chartData)
            ->with('dataWarnings', $dataWarnings)
            ->with('issueDates', $issueDates);
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
     * Get chart data for a specific asset's position history.
     */
    protected function getChartData($assetId, $fundId = null, $startDt = null, $endDt = null)
    {
        $query = PortfolioAsset::where('asset_id', $assetId)
            ->orderBy('start_dt');

        if ($fundId && $fundId !== 'none') {
            $query->whereHas('portfolio', function ($q) use ($fundId) {
                $q->where('fund_id', $fundId);
            });
        }

        // Use same "active during period" logic as table filter
        if ($startDt) {
            $query->where('end_dt', '>=', $startDt);
        }
        if ($endDt) {
            $query->where('start_dt', '<=', $endDt);
        }

        $positions = $query->get();

        if ($positions->isEmpty()) {
            return null;
        }

        $asset = AssetExt::find($assetId);

        $labels = [];
        $data = [];
        $today = now()->format('Y-m-d');
        $chartEndDate = $endDt ?: $today;

        foreach ($positions as $pos) {
            // Add start point
            $labels[] = $pos->start_dt->format('Y-m-d');
            $data[] = (float) $pos->position;

            // For closed positions, add end date with same value (stepped chart needs this)
            if ($pos->end_dt && $pos->end_dt->format('Y') !== '9999') {
                $labels[] = $pos->end_dt->format('Y-m-d');
                $data[] = (float) $pos->position;
            }
        }

        // Add final point at chart end date
        $lastPos = $positions->last();
        $labels[] = $chartEndDate;
        // If position is closed (end_dt not 9999), final value is 0
        if ($lastPos->end_dt && $lastPos->end_dt->format('Y') !== '9999') {
            $data[] = 0;
        } else {
            $data[] = (float) $lastPos->position;
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'assetName' => $asset->name ?? 'Unknown',
        ];
    }

    /**
     * Get chart data for specifically selected assets (multi-select).
     */
    protected function getSelectedAssetsChartData($assetIds, $fundId = null, $startDt = null, $endDt = null)
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
            $posQuery = PortfolioAsset::where('asset_id', $asset->id)
                ->orderBy('start_dt');

            if ($fundId && $fundId !== 'none') {
                $posQuery->whereHas('portfolio', function ($q) use ($fundId) {
                    $q->where('fund_id', $fundId);
                });
            }

            if ($startDt) {
                $posQuery->where('end_dt', '>=', $startDt);
            }
            if ($endDt) {
                $posQuery->where('start_dt', '<=', $endDt);
            }

            $positions = $posQuery->get();
            $today = now()->format('Y-m-d');
            $chartEndDate = $endDt ?: $today;

            // Build data points
            $dataPoints = [];
            foreach ($positions as $pos) {
                // Add start point
                $allDates->push($pos->start_dt->format('Y-m-d'));
                $dataPoints[] = [
                    'x' => $pos->start_dt->format('Y-m-d'),
                    'y' => (float) $pos->position
                ];

                // For closed positions, add end date with same value (stepped chart needs this)
                if ($pos->end_dt && $pos->end_dt->format('Y') !== '9999') {
                    $allDates->push($pos->end_dt->format('Y-m-d'));
                    $dataPoints[] = [
                        'x' => $pos->end_dt->format('Y-m-d'),
                        'y' => (float) $pos->position
                    ];
                }
            }

            // Add final point at chart end date
            if ($positions->isNotEmpty()) {
                $lastPos = $positions->last();
                $allDates->push($chartEndDate);
                // If position is closed (end_dt not 9999), final value is 0
                $finalValue = ($lastPos->end_dt && $lastPos->end_dt->format('Y') !== '9999') ? 0 : (float) $lastPos->position;
                $dataPoints[] = [
                    'x' => $chartEndDate,
                    'y' => $finalValue
                ];
            }

            $color = $colors[$index % count($colors)];
            $datasets[] = [
                'label' => $asset->name,
                'data' => $dataPoints,
                'borderColor' => $color,
                'backgroundColor' => $color . '20',
                'fill' => false,
                'pointRadius' => 3,
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
     * Get chart data for multiple assets in a fund (when no specific asset selected).
     * Only returns data if there are 8 or fewer unique assets.
     */
    protected function getMultiAssetChartData($fundId, $startDt = null, $endDt = null)
    {
        // Get unique asset IDs for this fund within the date range
        $query = PortfolioAsset::whereHas('portfolio', function ($q) use ($fundId) {
            $q->where('fund_id', $fundId);
        });

        if ($startDt) {
            $query->where('end_dt', '>=', $startDt);
        }
        if ($endDt) {
            $query->where('start_dt', '<=', $endDt);
        }

        $assetIds = $query->distinct()->pluck('asset_id');

        // Only show chart if 8 or fewer assets
        if ($assetIds->count() > 8 || $assetIds->isEmpty()) {
            return null;
        }

        $assets = AssetExt::whereIn('id', $assetIds)->orderBy('name')->get();
        $datasets = [];
        $allDates = collect();

        // Color palette for multiple lines
        $colors = [
            '#16a34a', '#2563eb', '#dc2626', '#9333ea',
            '#ea580c', '#0891b2', '#4f46e5', '#be185d'
        ];

        foreach ($assets as $index => $asset) {
            $posQuery = PortfolioAsset::where('asset_id', $asset->id)
                ->whereHas('portfolio', function ($q) use ($fundId) {
                    $q->where('fund_id', $fundId);
                })
                ->orderBy('start_dt');

            if ($startDt) {
                $posQuery->where('end_dt', '>=', $startDt);
            }
            if ($endDt) {
                $posQuery->where('start_dt', '<=', $endDt);
            }

            $positions = $posQuery->get();
            $today = now()->format('Y-m-d');
            $chartEndDate = $endDt ?: $today;

            // Build data points
            $dataPoints = [];
            foreach ($positions as $pos) {
                // Add start point
                $allDates->push($pos->start_dt->format('Y-m-d'));
                $dataPoints[] = [
                    'x' => $pos->start_dt->format('Y-m-d'),
                    'y' => (float) $pos->position
                ];

                // For closed positions, add end date with same value (stepped chart needs this)
                if ($pos->end_dt && $pos->end_dt->format('Y') !== '9999') {
                    $allDates->push($pos->end_dt->format('Y-m-d'));
                    $dataPoints[] = [
                        'x' => $pos->end_dt->format('Y-m-d'),
                        'y' => (float) $pos->position
                    ];
                }
            }

            // Add final point at chart end date
            if ($positions->isNotEmpty()) {
                $lastPos = $positions->last();
                $allDates->push($chartEndDate);
                // If position is closed (end_dt not 9999), final value is 0
                $finalValue = ($lastPos->end_dt && $lastPos->end_dt->format('Y') !== '9999') ? 0 : (float) $lastPos->position;
                $dataPoints[] = [
                    'x' => $chartEndDate,
                    'y' => $finalValue
                ];
            }

            $color = $colors[$index % count($colors)];
            $datasets[] = [
                'label' => $asset->name,
                'data' => $dataPoints,
                'borderColor' => $color,
                'backgroundColor' => $color . '20',
                'fill' => false,
                'pointRadius' => 3,
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
     * Get portfolio map for dropdown (used in create/edit forms).
     */
    protected function getPortfolioMap()
    {
        $portfolios = Portfolio::with('fund')->get();
        $map = ['none' => 'Select Portfolio'];
        foreach ($portfolios as $portfolio) {
            $fundName = $portfolio->fund->name ?? 'Unknown Fund';
            $map[$portfolio->id] = $fundName . ' - Portfolio #' . $portfolio->id;
        }
        return $map;
    }

    /**
     * Show the form for creating a new PortfolioAsset.
     */
    public function create()
    {
        $api = [
            'assetMap' => AssetExt::assetMap(),
            'portfolioMap' => $this->getPortfolioMap(),
        ];

        return view('portfolio_assets.create')->with('api', $api);
    }

    /**
     * Show the form for editing the specified PortfolioAsset.
     */
    public function edit($id)
    {
        $portfolioAsset = $this->portfolioAssetRepository->find($id);

        if (empty($portfolioAsset)) {
            \Flash::error('Portfolio Asset not found');
            return redirect(route('portfolioAssets.index'));
        }

        $api = [
            'assetMap' => AssetExt::assetMap(),
            'portfolioMap' => $this->getPortfolioMap(),
        ];

        return view('portfolio_assets.edit')
            ->with('portfolioAsset', $portfolioAsset)
            ->with('api', $api);
    }
}
