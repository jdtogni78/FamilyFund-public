<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateAssetAPIRequest;
use App\Http\Requests\API\UpdateAssetAPIRequest;
use App\Models\Asset;
use App\Repositories\AssetRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\AssetResource;
use Response;

/**
 * Class AssetController
 * @package App\Http\Controllers\API
 */

class AssetAPIController extends AppBaseController
{
    /** @var  AssetRepository */
    protected $assetRepository;

    public function __construct(AssetRepository $assetRepo)
    {
        $this->assetRepository = $assetRepo;
    }

    /**
     * Display a listing of the Asset.
     * GET|HEAD /assets
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $assets = $this->assetRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(AssetResource::collection($assets), 'Assets retrieved successfully');
    }

    /**
     * Store a newly created Asset in storage.
     * POST /assets
     *
     * @param CreateAssetAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateAssetAPIRequest $request)
    {
        $input = $request->all();

        $asset = $this->assetRepository->create($input);

        return $this->sendResponse(new AssetResource($asset), 'Asset saved successfully');
    }

    /**
     * Display the specified Asset.
     * GET|HEAD /assets/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var Asset $asset */
        $asset = $this->assetRepository->find($id);

        if (empty($asset)) {
            return $this->sendError('Asset not found');
        }

        return $this->sendResponse(new AssetResource($asset), 'Asset retrieved successfully');
    }

    /**
     * Update the specified Asset in storage.
     * PUT/PATCH /assets/{id}
     *
     * @param int $id
     * @param UpdateAssetAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAssetAPIRequest $request)
    {
        $input = $request->all();

        /** @var Asset $asset */
        $asset = $this->assetRepository->find($id);

        if (empty($asset)) {
            return $this->sendError('Asset not found');
        }

        $asset = $this->assetRepository->update($input, $id);

        return $this->sendResponse(new AssetResource($asset), 'Asset updated successfully');
    }

    /**
     * Remove the specified Asset from storage.
     * DELETE /assets/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var Asset $asset */
        $asset = $this->assetRepository->find($id);

        if (empty($asset)) {
            return $this->sendError('Asset not found');
        }

        $asset->delete();

        return $this->sendSuccess('Asset deleted successfully');
    }
}
