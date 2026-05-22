<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\FundTrait;
use App\Http\Controllers\Traits\ScheduledJobTrait;
use App\Http\Controllers\Traits\TransactionTrait;
use App\Http\Requests\API\CreateScheduledJobAPIRequest;
use App\Http\Requests\API\UpdateScheduledJobAPIRequest;
use App\Http\Resources\ScheduledJobResource;
use App\Models\ScheduledJob;
use App\Repositories\ScheduledJobRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Class ScheduledJobController
 * @package App\Http\Controllers\API
 */

class ScheduledJobAPIControllerExt extends AppBaseController
{
    use ScheduledJobTrait;

    /** @var  ScheduledJobRepository */
    private $scheduledJobRepository;

    // contructor
    public function __construct(ScheduledJobRepository $scheduledJobRepo)
    {
        $this->scheduledJobRepository = $scheduledJobRepo;
        $this->setupHandlers();
    }

    public function scheduleJobs(Request $request)
    {
        $asOfInput = $request->input('as_of', Carbon::now());
        $asOf = $asOfInput instanceof Carbon ? $asOfInput : Carbon::parse($asOfInput);
        $entityDescrFilter = $request->input('entity_descr', null);
        list ($ret, $errors) = $this->scheduleDueJobs($asOf, $entityDescrFilter);
        if (count($errors) > 0) {
            return $this->sendError('Errors scheduling jobs: ' . implode(', ', $errors));
        }
        return $this->sendResponse($ret, 'Scheduled jobs retrieved successfully');
    }

    // --- inlined from former base ---

    public function index(Request $request)
    {
        $scheduledJobs = $this->scheduledJobRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(ScheduledJobResource::collection($scheduledJobs), 'Scheduled Jobs retrieved successfully');
    }

    public function store(CreateScheduledJobAPIRequest $request)
    {
        $input = $request->all();

        $scheduledJob = $this->scheduledJobRepository->create($input);

        return $this->sendResponse(new ScheduledJobResource($scheduledJob), 'Scheduled Job saved successfully');
    }

    public function show($id)
    {
        /** @var ScheduledJob $scheduledJob */
        $scheduledJob = $this->scheduledJobRepository->find($id);

        if (empty($scheduledJob)) {
            return $this->sendError('Scheduled Job not found');
        }

        return $this->sendResponse(new ScheduledJobResource($scheduledJob), 'Scheduled Job retrieved successfully');
    }

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
