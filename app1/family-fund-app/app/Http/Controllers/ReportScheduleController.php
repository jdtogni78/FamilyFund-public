<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateReportScheduleRequest;
use App\Http\Requests\UpdateReportScheduleRequest;
use App\Repositories\ReportScheduleRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class ReportScheduleController extends AppBaseController
{
    /** @var ReportScheduleRepository $reportScheduleRepository*/
    public $reportScheduleRepository;

    public function __construct(ReportScheduleRepository $reportScheduleRepo)
    {
        $this->reportScheduleRepository = $reportScheduleRepo;
    }

    /**
     * Display a listing of the ReportSchedule.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $reportSchedules = $this->reportScheduleRepository->all();

        return view('report_schedules.index')
            ->with('reportSchedules', $reportSchedules);
    }

    /**
     * Show the form for creating a new ReportSchedule.
     *
     * @return Response
     */
    public function create()
    {
        return view('report_schedules.create');
    }

    /**
     * Store a newly created ReportSchedule in storage.
     *
     * @param CreateReportScheduleRequest $request
     *
     * @return Response
     */
    public function store(CreateReportScheduleRequest $request)
    {
        $input = $request->all();

        $reportSchedule = $this->reportScheduleRepository->create($input);

        Flash::success('Report Schedule saved successfully.');

        return redirect(route('reportSchedules.index'));
    }

    /**
     * Display the specified ReportSchedule.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $reportSchedule = $this->reportScheduleRepository->find($id);

        if (empty($reportSchedule)) {
            Flash::error('Report Schedule not found');

            return redirect(route('reportSchedules.index'));
        }

        return view('report_schedules.show')->with('reportSchedule', $reportSchedule);
    }

    /**
     * Show the form for editing the specified ReportSchedule.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $reportSchedule = $this->reportScheduleRepository->find($id);

        if (empty($reportSchedule)) {
            Flash::error('Report Schedule not found');

            return redirect(route('reportSchedules.index'));
        }

        return view('report_schedules.edit')->with('reportSchedule', $reportSchedule);
    }

    /**
     * Update the specified ReportSchedule in storage.
     *
     * @param int $id
     * @param UpdateReportScheduleRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateReportScheduleRequest $request)
    {
        $reportSchedule = $this->reportScheduleRepository->find($id);

        if (empty($reportSchedule)) {
            Flash::error('Report Schedule not found');

            return redirect(route('reportSchedules.index'));
        }

        $reportSchedule = $this->reportScheduleRepository->update($request->all(), $id);

        Flash::success('Report Schedule updated successfully.');

        return redirect(route('reportSchedules.index'));
    }

    /**
     * Remove the specified ReportSchedule from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $reportSchedule = $this->reportScheduleRepository->find($id);

        if (empty($reportSchedule)) {
            Flash::error('Report Schedule not found');

            return redirect(route('reportSchedules.index'));
        }

        $this->reportScheduleRepository->delete($id);

        Flash::success('Report Schedule deleted successfully.');

        return redirect(route('reportSchedules.index'));
    }
}
