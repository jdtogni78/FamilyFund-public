<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\CreateScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Schedule;
use App\Models\ScheduleExt;
use App\Repositories\ScheduleRepository;
use App\Http\Controllers\ScheduleController;
use Illuminate\Http\Request;
use Flash;
use Response;

class ScheduleControllerExt extends ScheduleController
{
    public function create()
    {
        $api = [
            'typeMap' => ScheduleExt::$typeMap,
        ];

        return view('schedules.create')
            ->with('api', $api);
    }

    public function edit($id)
    {
        $schedule = $this->ScheduleRepository->find($id);

        if (empty($schedule)) {
            Flash::error('Schedule not found');

            return redirect(route('schedules.index'));
        }

        $api = [
            'typeMap' => ScheduleExt::$typeMap,
        ];

        return view('schedules.edit')
            ->with('schedule', $schedule)
            ->with('api', $api);
    }
}
