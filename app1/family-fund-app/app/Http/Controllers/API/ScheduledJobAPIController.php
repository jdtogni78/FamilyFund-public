<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateScheduledJobAPIRequest;
use App\Http\Requests\API\UpdateScheduledJobAPIRequest;
use App\Models\ScheduledJob;
use App\Repositories\ScheduledJobRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\ScheduledJobResource;
use Response;

/**
 * Class ScheduledJobController
 * @package App\Http\Controllers\API
 */

class ScheduledJobAPIController extends AppBaseController
{
    /** @var  ScheduledJobRepository */
    private $scheduledJobRepository;

    public function __construct(ScheduledJobRepository $scheduledJobRepo)
    {
        $this->scheduledJobRepository = $scheduledJobRepo;
    }

    /**
     * Display a listing of the ScheduledJob.
     * GET|HEAD /scheduledJobs
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $scheduledJobs = $this->scheduledJobRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(ScheduledJobResource::collection($scheduledJobs), 'Scheduled Jobs retrieved successfully');
    }

    /**
     * Store a newly created ScheduledJob in storage.
     * POST /scheduledJobs
     *
     * @param CreateScheduledJobAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateScheduledJobAPIRequest $request)
    {
        $input = $request->all();

        $scheduledJob = $this->scheduledJobRepository->create($input);

        return $this->sendResponse(new ScheduledJobResource($scheduledJob), 'Scheduled Job saved successfully');
    }

    /**
     * Display the specified ScheduledJob.
     * GET|HEAD /scheduledJobs/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var ScheduledJob $scheduledJob */
        $scheduledJob = $this->scheduledJobRepository->find($id);

        if (empty($scheduledJob)) {
            return $this->sendError('Scheduled Job not found');
        }

        return $this->sendResponse(new ScheduledJobResource($scheduledJob), 'Scheduled Job retrieved successfully');
    }

    /**
     * Update the specified ScheduledJob in storage.
     * PUT/PATCH /scheduledJobs/{id}
     *
     * @param int $id
     * @param UpdateScheduledJobAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateScheduledJobAPIRequest $request)
    {
        $input = $request->all();

        /** @var ScheduledJob $scheduledJob */
        $scheduledJob = $this->scheduledJobRepository->find($id);

        if (empty($scheduledJob)) {
            return $this->sendError('Scheduled Job not found');
        }

        $scheduledJob = $this->scheduledJobRepository->update($input, $id);

        return $this->sendResponse(new ScheduledJobResource($scheduledJob), 'ScheduledJob updated successfully');
    }

    /**
     * Remove the specified ScheduledJob from storage.
     * DELETE /scheduledJobs/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var ScheduledJob $scheduledJob */
        $scheduledJob = $this->scheduledJobRepository->find($id);

        if (empty($scheduledJob)) {
            return $this->sendError('Scheduled Job not found');
        }

        $scheduledJob->delete();

        return $this->sendSuccess('Scheduled Job deleted successfully');
    }
}
