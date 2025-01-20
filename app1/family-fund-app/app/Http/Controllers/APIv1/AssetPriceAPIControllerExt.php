<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\API\AssetPriceAPIController;
use App\Http\Controllers\Traits\BulkStoreTrait;
use App\Http\Requests\API\CreateAssetPriceAPIRequest;
use App\Http\Requests\API\CreatePriceUpdateAPIRequest;
use App\Http\Resources\AssetPriceResource;
use App\Models\AssetPrice;
use App\Repositories\AssetPriceRepository;
use DB;
use Exception;
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

        $assetPrice = $this->insertHistoricalPrice($input['asset_id'], $input['start_dt'], $input['price']);

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
}
