<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\API\AssetPriceAPIController;
use App\Http\Controllers\Traits\BulkStoreTrait;
use App\Http\Requests\API\CreateAssetPriceAPIRequest;
use App\Http\Requests\API\CreatePriceUpdateAPIRequest;
use App\Http\Resources\AssetPriceResource;
use App\Models\AssetPrice;
use App\Repositories\AssetPriceRepository;
use App\Services\AssetPriceGapService;
use DB;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetPriceAPIControllerExt extends AssetPriceAPIController
{
    use BulkStoreTrait;

    public function __construct(AssetPriceRepository $assetPricesRepo)
    {
        parent::__construct($assetPricesRepo);
    }

    /**
     * Store a newly created AssetPriceCollection in storage.
     * POST /api/asset_prices_bulk_update
     *
     * @param CreatePriceUpdateAPIRequest $request
     *
     * @return Response
     * @throws Exception
     */
    public function bulkStore(CreatePriceUpdateAPIRequest $request)
    {
        DB::beginTransaction();
        try {
            // $this->verbose = true;
            $this->genericBulkStore($request, 'price');
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        DB::commit();
        return $this->sendResponse([], 'Bulk price update successful!');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function store(CreateAssetPriceAPIRequest $request)
    {
        $input = $request->all();

        // Convert start_dt to Carbon if it's a string
        $timestamp = $input['start_dt'];
        if (is_string($timestamp)) {
            $timestamp = \Carbon\Carbon::parse($timestamp);
        }

        $assetPrice = $this->insertHistorical(null, $input['asset_id'], $timestamp, $input['price'], 'price');

        return $this->sendResponse(new AssetPriceResource($assetPrice), 'Asset Price saved successfully');
    }

    protected function createChild($data, $source)
    {
        $ap = AssetPrice::create($data);
        if ($data['price'] != $ap->price) {
            $this->warn("Price was adjusted from ".$data['price']." to ".$ap->price);
        }
        return $ap;
    }

    public function getQuery($source, $asset, $timestamp)
    {
        $query = $asset->priceAsOf($timestamp);
        return $query;
    }

    /**
     * Get missing asset price dates for trading days
     * GET /api/asset_prices/gaps?days=30&exchange=NYSE
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function gaps(Request $request): JsonResponse
    {
        $days = $request->query('days', 30);
        $exchange = $request->query('exchange', 'NYSE');

        // Validate days parameter
        if (!is_numeric($days) || $days < 1 || $days > 365) {
            return response()->json([
                'error' => 'days parameter must be between 1 and 365'
            ], Response::HTTP_BAD_REQUEST);
        }

        $gapService = new AssetPriceGapService();
        $missingDates = $gapService->findGaps((int)$days, $exchange);

        return response()->json([
            'lookback_days' => (int)$days,
            'exchange' => $exchange,
            'missing_count' => count($missingDates),
            'missing_dates' => $missingDates,
        ]);
    }
}
