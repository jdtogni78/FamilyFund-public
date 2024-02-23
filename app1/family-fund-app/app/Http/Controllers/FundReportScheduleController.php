<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFundReportScheduleRequest;
use App\Http\Requests\UpdateFundReportScheduleRequest;
use App\Repositories\FundReportScheduleRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class FundReportScheduleController extends AppBaseController
{
    /** @var FundReportScheduleRepository $fundReportScheduleRepository*/
    protected $fundReportScheduleRepository;

    public function __construct(FundReportScheduleRepository $fundReportScheduleRepo)
    {
        $this->fundReportScheduleRepository = $fundReportScheduleRepo;
    }

    /**
     * Display a listing of the FundReportSchedule.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $fundReportSchedules = $this->fundReportScheduleRepository->all();

        return view('fund_report_schedules.index')
            ->with('fundReportSchedules', $fundReportSchedules);
    }

    /**
     * Show the form for creating a new FundReportSchedule.
     *
     * @return Response
     */
    public function create()
    {
        return view('fund_report_schedules.create');
    }

    /**
     * Store a newly created FundReportSchedule in storage.
     *
     * @param CreateFundReportScheduleRequest $request
     *
     * @return Response
     */
    public function store(CreateFundReportScheduleRequest $request)
    {
        $input = $request->all();

        $fundReportSchedule = $this->fundReportScheduleRepository->create($input);

        Flash::success('Fund Report Schedule saved successfully.');

        return redirect(route('fundReportSchedules.index'));
    }

    /**
     * Display the specified FundReportSchedule.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $fundReportSchedule = $this->fundReportScheduleRepository->find($id);

        if (empty($fundReportSchedule)) {
            Flash::error('Fund Report Schedule not found');

            return redirect(route('fundReportSchedules.index'));
        }

        return view('fund_report_schedules.show')->with('fundReportSchedule', $fundReportSchedule);
    }

    /**
     * Show the form for editing the specified FundReportSchedule.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $fundReportSchedule = $this->fundReportScheduleRepository->find($id);

        if (empty($fundReportSchedule)) {
            Flash::error('Fund Report Schedule not found');

            return redirect(route('fundReportSchedules.index'));
        }

        return view('fund_report_schedules.edit')->with('fundReportSchedule', $fundReportSchedule);
    }

    /**
     * Update the specified FundReportSchedule in storage.
     *
     * @param int $id
     * @param UpdateFundReportScheduleRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateFundReportScheduleRequest $request)
    {
        $fundReportSchedule = $this->fundReportScheduleRepository->find($id);

        if (empty($fundReportSchedule)) {
            Flash::error('Fund Report Schedule not found');

            return redirect(route('fundReportSchedules.index'));
        }

        $fundReportSchedule = $this->fundReportScheduleRepository->update($request->all(), $id);

        Flash::success('Fund Report Schedule updated successfully.');

        return redirect(route('fundReportSchedules.index'));
    }

    /**
     * Remove the specified FundReportSchedule from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $fundReportSchedule = $this->fundReportScheduleRepository->find($id);

        if (empty($fundReportSchedule)) {
            Flash::error('Fund Report Schedule not found');

            return redirect(route('fundReportSchedules.index'));
        }

        $this->fundReportScheduleRepository->delete($id);

        Flash::success('Fund Report Schedule deleted successfully.');

        return redirect(route('fundReportSchedules.index'));
    }
}
