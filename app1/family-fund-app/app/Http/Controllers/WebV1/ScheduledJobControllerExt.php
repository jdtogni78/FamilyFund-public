<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\CreateScheduledJobRequest;
use App\Http\Requests\UpdateScheduledJobRequest;
use App\Repositories\ScheduledJobRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;
use App\Models\ScheduledJobExt;
use App\Http\Controllers\ScheduledJobController;
use App\Http\Controllers\Traits\ScheduledJobTrait;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\FundReportExt;
use App\Models\TransactionExt;
use App\Models\TradeBandReport;
use App\Models\Schedule;
use App\Models\FundExt;
use Illuminate\Support\MessageBag;

class ScheduledJobControllerExt extends ScheduledJobController
{
    use ScheduledJobTrait;

    public function index(Request $request)
    {
        $scheduledJobs = ScheduledJobExt::with(['schedule', 'fund', 'portfolio'])
            ->orderBy('start_dt', 'desc')
            ->get();

        return view('scheduled_jobs.index')
            ->with('scheduledJobs', $scheduledJobs);
    }

    public function create()
    {
        return view('scheduled_jobs.create')
            ->with($this->getFormData());
    }

    public function edit($id)
    {
        $scheduledJob = $this->scheduledJobRepository->find($id);

        if (empty($scheduledJob)) {
            Flash::error('Scheduled Job not found');
            return redirect(route('scheduledJobs.index'));
        }

        return view('scheduled_jobs.edit')
            ->with('scheduledJob', $scheduledJob)
            ->with($this->getFormData());
    }

    private function getFormData()
    {
        $schedules = Schedule::orderBy('descr')->pluck('descr', 'id');
        $funds = FundExt::orderBy('name')->get()->mapWithKeys(function ($fund) {
            return [$fund->id => $fund->name];
        });

        return [
            'schedules' => $schedules,
            'funds' => $funds,
            'entityTypes' => ScheduledJobExt::$entityMap,
        ];
    }

    public function previewScheduledJob($id, $asOf)
    {
        $scheduledJob = ScheduledJobExt::find($id);

        if (empty($scheduledJob)) {
            Flash::error('Scheduled Job not found');
            return redirect()->back()->withErrors('Scheduled Job not found');
        }

        $children = $this->getChildren($scheduledJob);

        $errors = [];
        DB::beginTransaction();
        try {
            list ($data, $error, $shouldRunBy) = $this->scheduleDueJob(new Carbon($asOf), $scheduledJob);
            if (null !== $error) $errors[] = $error->getMessage();
            DB::rollBack();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        Log::info('Errors: ' . json_encode($errors));

        return view('scheduled_jobs.preview')
            ->with('scheduledJob', $scheduledJob)
            ->with('data', $data)
            ->with('children', $children)
            ->with('asOf', $asOf)
            ->with('shouldRunBy', $shouldRunBy)
            ->withErrors(new MessageBag($errors));
    }

    public function runScheduledJob($id, $asOf)
    {
        $scheduledJob = ScheduledJobExt::find($id);

        if (empty($scheduledJob)) {
            Flash::error('Scheduled Job not found');
            return redirect()->back()->withErrors('Scheduled Job not found');
        }

        DB::beginTransaction();
        try {
            list ($data, $error, $shouldRunBy) = $this->scheduleDueJob(new Carbon($asOf), $scheduledJob);

            if (null !== $error) {
                DB::rollBack();
                Flash::error('Job failed: ' . $error->getMessage());
            } elseif (null === $data) {
                DB::rollBack();
                $reason = 'Job not due or no data available.';
                if ($shouldRunBy['shouldRunBy']->gt(new Carbon($asOf))) {
                    $reason = 'Job not due until ' . $shouldRunBy['shouldRunBy']->toDateString();
                }
                Flash::warning('Job did not run: ' . $reason);
            } else {
                DB::commit();
                Flash::success('Scheduled job executed successfully. Email will be sent via queue.');
                Log::info('Scheduled job executed: ' . $scheduledJob->id . ', created: ' . get_class($data) . ' #' . $data->id);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Scheduled job failed: ' . $e->getMessage());
            Flash::error('Job failed: ' . $e->getMessage());
        }

        return redirect(route('scheduledJobs.show', $id));
    }

    public function forceRunScheduledJob($id, $asOf)
    {
        $scheduledJob = ScheduledJobExt::find($id);

        if (empty($scheduledJob)) {
            Flash::error('Scheduled Job not found');
            return redirect()->back()->withErrors('Scheduled Job not found');
        }

        DB::beginTransaction();
        try {
            list ($data, $error) = $this->forceRunJob(new Carbon($asOf), $scheduledJob);

            if (null !== $error) {
                DB::rollBack();
                Flash::error('Job failed: ' . $error->getMessage());
            } elseif (null === $data) {
                DB::rollBack();
                Flash::warning('Job did not produce any output.');
            } else {
                DB::commit();
                Flash::success('Scheduled job force executed successfully. Email will be sent via queue.');
                Log::info('Force executed scheduled job: ' . $scheduledJob->id . ', created: ' . get_class($data) . ' #' . $data->id);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Force run scheduled job failed: ' . $e->getMessage());
            Flash::error('Job failed: ' . $e->getMessage());
        }

        return redirect(route('scheduledJobs.show', $id));
    }

    private function getChildren(ScheduledJobExt $scheduledJob)
    {
        $children = null;
        if ($scheduledJob->entity_descr == ScheduledJobExt::ENTITY_FUND_REPORT) {
            $children = FundReportExt::query()
                ->where('scheduled_job_id', $scheduledJob->id)
                ->get();
        } else if ($scheduledJob->entity_descr == ScheduledJobExt::ENTITY_TRANSACTION) {
            $children = TransactionExt::query()
                ->where('scheduled_job_id', $scheduledJob->id)
                ->get();
        } else if ($scheduledJob->entity_descr == ScheduledJobExt::ENTITY_TRADE_BAND_REPORT) {
            $children = TradeBandReport::query()
                ->where('scheduled_job_id', $scheduledJob->id)
                ->get();
        }
        return $children;
    }
}
