<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateScheduleAPIRequest;
use App\Http\Requests\API\UpdateScheduleAPIRequest;
use App\Models\Schedule;
use App\Repositories\ScheduleRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\ScheduleResource;
use Response;

/**
 * Class ScheduleController
 * @package App\Http\Controllers\API
 */

class ScheduleAPIController extends AppBaseController
{
    /** @var  ScheduleRepository */
    private $scheduleRepository;

    public function __construct(ScheduleRepository $scheduleRepo)
    {
        $this->scheduleRepository = $scheduleRepo;
    }

    /**
     * Display a listing of the Schedule.
     * GET|HEAD /schedules
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $schedules = $this->scheduleRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(ScheduleResource::collection($schedules), 'Schedules retrieved successfully');
    }

    /**
     * Store a newly created Schedule in storage.
     * POST /schedules
     *
     * @param CreateScheduleAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateScheduleAPIRequest $request)
    {
        $input = $request->all();

        $schedule = $this->scheduleRepository->create($input);

        return $this->sendResponse(new ScheduleResource($schedule), 'Schedule saved successfully');
    }

    /**
     * Display the specified Schedule.
     * GET|HEAD /schedules/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var Schedule $schedule */
        $schedule = $this->scheduleRepository->find($id);

        if (empty($schedule)) {
            return $this->sendError('Schedule not found');
        }

        return $this->sendResponse(new ScheduleResource($schedule), 'Schedule retrieved successfully');
    }

    /**
     * Update the specified Schedule in storage.
     * PUT/PATCH /schedules/{id}
     *
     * @param int $id
     * @param UpdateScheduleAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateScheduleAPIRequest $request)
    {
        $input = $request->all();

        /** @var Schedule $schedule */
        $schedule = $this->scheduleRepository->find($id);

        if (empty($schedule)) {
            return $this->sendError('Schedule not found');
        }

        $schedule = $this->scheduleRepository->update($input, $id);

        return $this->sendResponse(new ScheduleResource($schedule), 'Schedule updated successfully');
    }

    /**
     * Remove the specified Schedule from storage.
     * DELETE /schedules/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var Schedule $schedule */
        $schedule = $this->scheduleRepository->find($id);

        if (empty($schedule)) {
            return $this->sendError('Schedule not found');
        }

        $schedule->delete();

        return $this->sendSuccess('Schedule deleted successfully');
    }
}
