<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreatePortfolioAssetAPIRequest;
use App\Http\Requests\API\UpdatePortfolioAssetAPIRequest;
use App\Models\PortfolioAsset;
use App\Repositories\PortfolioAssetRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\PortfolioAssetResource;
use Response;

/**
 * Class PortfolioAssetController
 * @package App\Http\Controllers\API
 */

class PortfolioAssetAPIController extends AppBaseController
{
    /** @var  PortfolioAssetRepository */
    protected $portfolioAssetRepository;

    public function __construct(PortfolioAssetRepository $portfolioAssetRepo)
    {
        $this->portfolioAssetRepository = $portfolioAssetRepo;
    }

    /**
     * Display a listing of the PortfolioAsset.
     * GET|HEAD /portfolioAssets
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $portfolioAssets = $this->portfolioAssetRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );
//         $portfolioAssets = $this->portfolioAssetRepository->with(['asset'])->get();

        return $this->sendResponse(PortfolioAssetResource::collection($portfolioAssets), 'Portfolio Assets retrieved successfully');
    }

    /**
     * Store a newly created PortfolioAsset in storage.
     * POST /portfolioAssets
     *
     * @param CreatePortfolioAssetAPIRequest $request
     *
     * @return Response
     */
    public function store(CreatePortfolioAssetAPIRequest $request)
    {
        $input = $request->all();

        $portfolioAsset = $this->portfolioAssetRepository->create($input);

        return $this->sendResponse(new PortfolioAssetResource($portfolioAsset), 'Portfolio Asset saved successfully');
    }

    /**
     * Display the specified PortfolioAsset.
     * GET|HEAD /portfolioAssets/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var PortfolioAsset $portfolioAsset */
        $portfolioAsset = $this->portfolioAssetRepository->find($id);

        if (empty($portfolioAsset)) {
            return $this->sendError('Portfolio Asset not found');
        }

        return $this->sendResponse(new PortfolioAssetResource($portfolioAsset), 'Portfolio Asset retrieved successfully');
    }

    /**
     * Update the specified PortfolioAsset in storage.
     * PUT/PATCH /portfolioAssets/{id}
     *
     * @param int $id
     * @param UpdatePortfolioAssetAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePortfolioAssetAPIRequest $request)
    {
        $input = $request->all();

        /** @var PortfolioAsset $portfolioAsset */
        $portfolioAsset = $this->portfolioAssetRepository->find($id);

        if (empty($portfolioAsset)) {
            return $this->sendError('Portfolio Asset not found');
        }

        $portfolioAsset = $this->portfolioAssetRepository->update($input, $id);

        return $this->sendResponse(new PortfolioAssetResource($portfolioAsset), 'PortfolioAsset updated successfully');
    }

    /**
     * Remove the specified PortfolioAsset from storage.
     * DELETE /portfolioAssets/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var PortfolioAsset $portfolioAsset */
        $portfolioAsset = $this->portfolioAssetRepository->find($id);

        if (empty($portfolioAsset)) {
            return $this->sendError('Portfolio Asset not found');
        }

        $portfolioAsset->delete();

        return $this->sendSuccess('Portfolio Asset deleted successfully');
    }
}
