<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateChangeLogAPIRequest;
use App\Http\Requests\API\UpdateChangeLogAPIRequest;
use App\Models\ChangeLog;
use App\Repositories\ChangeLogRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\ChangeLogResource;
use Response;

/**
 * Class ChangeLogController
 * @package App\Http\Controllers\API
 */

class ChangeLogAPIController extends AppBaseController
{
    /** @var  ChangeLogRepository */
    private $changeLogRepository;

    public function __construct(ChangeLogRepository $changeLogRepo)
    {
        $this->changeLogRepository = $changeLogRepo;
    }

    /**
     * Display a listing of the ChangeLog.
     * GET|HEAD /changeLogs
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $changeLogs = $this->changeLogRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(ChangeLogResource::collection($changeLogs), 'Change Logs retrieved successfully');
    }

    /**
     * Store a newly created ChangeLog in storage.
     * POST /changeLogs
     *
     * @param CreateChangeLogAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateChangeLogAPIRequest $request)
    {
        $input = $request->all();

        $changeLog = $this->changeLogRepository->create($input);

        return $this->sendResponse(new ChangeLogResource($changeLog), 'Change Log saved successfully');
    }

    /**
     * Display the specified ChangeLog.
     * GET|HEAD /changeLogs/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var ChangeLog $changeLog */
        $changeLog = $this->changeLogRepository->find($id);

        if (empty($changeLog)) {
            return $this->sendError('Change Log not found');
        }

        return $this->sendResponse(new ChangeLogResource($changeLog), 'Change Log retrieved successfully');
    }

    /**
     * Update the specified ChangeLog in storage.
     * PUT/PATCH /changeLogs/{id}
     *
     * @param int $id
     * @param UpdateChangeLogAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateChangeLogAPIRequest $request)
    {
        $input = $request->all();

        /** @var ChangeLog $changeLog */
        $changeLog = $this->changeLogRepository->find($id);

        if (empty($changeLog)) {
            return $this->sendError('Change Log not found');
        }

        $changeLog = $this->changeLogRepository->update($input, $id);

        return $this->sendResponse(new ChangeLogResource($changeLog), 'ChangeLog updated successfully');
    }

    /**
     * Remove the specified ChangeLog from storage.
     * DELETE /changeLogs/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var ChangeLog $changeLog */
        $changeLog = $this->changeLogRepository->find($id);

        if (empty($changeLog)) {
            return $this->sendError('Change Log not found');
        }

        $changeLog->delete();

        return $this->sendSuccess('Change Log deleted successfully');
    }
}
