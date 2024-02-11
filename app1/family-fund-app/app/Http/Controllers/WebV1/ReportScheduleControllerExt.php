<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\CreateReportScheduleRequest;
use App\Http\Requests\UpdateReportScheduleRequest;
use App\Models\ReportSchedule;
use App\Models\ReportScheduleExt;
use App\Repositories\ReportScheduleRepository;
use App\Http\Controllers\ReportScheduleController;
use Illuminate\Http\Request;
use Flash;
use Response;

class ReportScheduleControllerExt extends ReportScheduleController
{

    public function create()
    {
        $api = [
            'typeMap' => ReportScheduleExt::$typeMap,
        ];

        return view('report_schedules.create')
            ->with('api', $api);
    }

    public function edit($id)
    {
        $reportSchedule = $this->reportScheduleRepository->find($id);

        if (empty($reportSchedule)) {
            Flash::error('Report Schedule not found');

            return redirect(route('reportSchedules.index'));
        }

        $api = [
            'typeMap' => ReportScheduleExt::$typeMap,
        ];

        return view('report_schedules.edit')
            ->with('reportSchedule', $reportSchedule)
            ->with('api', $api);
    }
}
