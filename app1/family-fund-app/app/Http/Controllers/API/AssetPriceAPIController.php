<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateAssetPriceAPIRequest;
use App\Http\Requests\API\UpdateAssetPriceAPIRequest;
use App\Models\AssetPrice;
use App\Repositories\AssetPriceRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\AssetPriceResource;
use Response;

/**
 * Class AssetPriceController
 * @package App\Http\Controllers\API
 */

class AssetPriceAPIController extends AppBaseController
{
    /** @var  AssetPriceRepository */
    protected $assetPriceRepository;

    public function __construct(AssetPriceRepository $assetPriceRepo)
    {
        $this->assetPriceRepository = $assetPriceRepo;
    }

    /**
     * Display a listing of the AssetPrice.
     * GET|HEAD /assetPrices
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $assetPrices = $this->assetPriceRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );
//         $assetPrices = $this->assetPricesRepository->with(['asset'])->get();

        return $this->sendResponse(AssetPriceResource::collection($assetPrices), 'Asset Prices retrieved successfully');
    }

    /**
     * Store a newly created AssetPrice in storage.
     * POST /assetPrices
     *
     * @param CreateAssetPriceAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateAssetPriceAPIRequest $request)
    {
        $input = $request->all();

        $assetPrice = $this->assetPriceRepository->create($input);

        return $this->sendResponse(new AssetPriceResource($assetPrice), 'Asset Price saved successfully');
    }

    /**
     * Display the specified AssetPrice.
     * GET|HEAD /assetPrices/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var AssetPrice $assetPrice */
        $assetPrice = $this->assetPriceRepository->find($id);

        if (empty($assetPrice)) {
            return $this->sendError('Asset Price not found');
        }

        return $this->sendResponse(new AssetPriceResource($assetPrice), 'Asset Price retrieved successfully');
    }

    /**
     * Update the specified AssetPrice in storage.
     * PUT/PATCH /assetPrices/{id}
     *
     * @param int $id
     * @param UpdateAssetPriceAPIRequest $request
     *
     * @return Response
     */
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

    /**
     * Remove the specified AssetPrice from storage.
     * DELETE /assetPrices/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
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
