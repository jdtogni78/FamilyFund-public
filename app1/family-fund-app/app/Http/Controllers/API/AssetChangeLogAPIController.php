<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateAssetChangeLogAPIRequest;
use App\Http\Requests\API\UpdateAssetChangeLogAPIRequest;
use App\Models\AssetChangeLog;
use App\Repositories\AssetChangeLogRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\AssetChangeLogResource;
use Response;

/**
 * Class AssetChangeLogController
 * @package App\Http\Controllers\API
 */

class AssetChangeLogAPIController extends AppBaseController
{
    /** @var  AssetChangeLogRepository */
    protected $assetChangeLogRepository;

    public function __construct(AssetChangeLogRepository $assetChangeLogRepo)
    {
        $this->assetChangeLogRepository = $assetChangeLogRepo;
    }

    /**
     * Display a listing of the AssetChangeLog.
     * GET|HEAD /assetChangeLogs
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $assetChangeLogs = $this->assetChangeLogRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(AssetChangeLogResource::collection($assetChangeLogs), 'Asset Change Logs retrieved successfully');
    }

    /**
     * Store a newly created AssetChangeLog in storage.
     * POST /assetChangeLogs
     *
     * @param CreateAssetChangeLogAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateAssetChangeLogAPIRequest $request)
    {
        $input = $request->all();

        $assetChangeLog = $this->assetChangeLogRepository->create($input);

        return $this->sendResponse(new AssetChangeLogResource($assetChangeLog), 'Asset Change Log saved successfully');
    }

    /**
     * Display the specified AssetChangeLog.
     * GET|HEAD /assetChangeLogs/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var AssetChangeLog $assetChangeLog */
        $assetChangeLog = $this->assetChangeLogRepository->find($id);

        if (empty($assetChangeLog)) {
            return $this->sendError('Asset Change Log not found');
        }

        return $this->sendResponse(new AssetChangeLogResource($assetChangeLog), 'Asset Change Log retrieved successfully');
    }

    /**
     * Update the specified AssetChangeLog in storage.
     * PUT/PATCH /assetChangeLogs/{id}
     *
     * @param int $id
     * @param UpdateAssetChangeLogAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAssetChangeLogAPIRequest $request)
    {
        $input = $request->all();

        /** @var AssetChangeLog $assetChangeLog */
        $assetChangeLog = $this->assetChangeLogRepository->find($id);

        if (empty($assetChangeLog)) {
            return $this->sendError('Asset Change Log not found');
        }

        $assetChangeLog = $this->assetChangeLogRepository->update($input, $id);

        return $this->sendResponse(new AssetChangeLogResource($assetChangeLog), 'AssetChangeLog updated successfully');
    }

    /**
     * Remove the specified AssetChangeLog from storage.
     * DELETE /assetChangeLogs/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var AssetChangeLog $assetChangeLog */
        $assetChangeLog = $this->assetChangeLogRepository->find($id);

        if (empty($assetChangeLog)) {
            return $this->sendError('Asset Change Log not found');
        }

        $assetChangeLog->delete();

        return $this->sendSuccess('Asset Change Log deleted successfully');
    }
}
