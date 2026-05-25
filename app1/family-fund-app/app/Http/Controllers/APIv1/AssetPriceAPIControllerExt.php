<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\AuthorizesApiAccess;
use App\Http\Controllers\Traits\BulkStoreTrait;
use App\Http\Requests\API\CreateAssetPriceAPIRequest;
use App\Http\Requests\API\CreatePriceUpdateAPIRequest;
use App\Http\Requests\API\UpdateAssetPriceAPIRequest;
use App\Http\Resources\AssetPriceResource;
use App\Models\AssetPrice;
use App\Repositories\AssetPriceRepository;
use App\Services\AssetPriceGapService;
use DB;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetPriceAPIControllerExt extends AppBaseController
{
    use AuthorizesApiAccess;
    use BulkStoreTrait;

    /** @var  AssetPriceRepository */
    protected $assetPriceRepository;

    public function __construct(AssetPriceRepository $assetPricesRepo)
    {
        $this->assetPriceRepository = $assetPricesRepo;

        // Asset prices are global reference data: reads stay open to any
        // authenticated caller, but every write — the generated resource
        // store/update/destroy AND the bulk price feed (asset_prices_bulk_update)
        // — is system-admin-only (#82, flag-gated). dstrader pushes prices as a
        // system-admin service user. Gating as controller middleware (not
        // in-method) rejects before FormRequest validation.
        $this->middleware($this->adminWriteMiddleware())->only(['store', 'update', 'destroy', 'bulkStore']);
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
        // Authz: gated to system-admin by the ctor adminWriteMiddleware()
        // (#82). Sibling bulk endpoints portfolio_assets_bulk_update /
        // portfolio_balances_bulk_update guard fund-scoped data via
        // requireFullAccessToAnyFund(), which system admins also satisfy.
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

    // --- inlined from former base ---

    public function index(Request $request)
    {
        $assetPrices = $this->assetPriceRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(AssetPriceResource::collection($assetPrices), 'Asset Prices retrieved successfully');
    }

    public function show($id)
    {
        /** @var AssetPrice $assetPrice */
        $assetPrice = $this->assetPriceRepository->find($id);

        if (empty($assetPrice)) {
            return $this->sendError('Asset Price not found');
        }

        return $this->sendResponse(new AssetPriceResource($assetPrice), 'Asset Price retrieved successfully');
    }

    public function update($id, UpdateAssetPriceAPIRequest $request)
    {
        $input = $request->all();

        /** @var AssetPrice $assetPrice */
        $assetPrice = $this->assetPriceRepository->find($id);

        if (empty($assetPrice)) {
            return $this->sendError('Asset Price not found');
        }

        $assetPrice = $this->assetPriceRepository->update($input, $id);

        return $this->sendResponse(new AssetPriceResource($assetPrice), 'AssetPrice updated successfully');
    }

    public function destroy($id)
    {
        /** @var AssetPrice $assetPrice */
        $assetPrice = $this->assetPriceRepository->find($id);

        if (empty($assetPrice)) {
            return $this->sendError('Asset Price not found');
        }

        $assetPrice->delete();

        return $this->sendSuccess('Asset Price deleted successfully');
    }
}
