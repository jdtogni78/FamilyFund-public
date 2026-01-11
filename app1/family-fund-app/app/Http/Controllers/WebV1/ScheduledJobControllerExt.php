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
use App\Models\PortfolioReportExt;
use App\Models\Schedule;
use App\Models\FundExt;
use App\Models\Portfolio;
use Illuminate\Support\MessageBag;

class ScheduledJobControllerExt extends ScheduledJobController
{
    use ScheduledJobTrait;

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
        $portfolios = Portfolio::orderBy('name')->get()->mapWithKeys(function ($portfolio) {
            return [$portfolio->id => $portfolio->name . ' (Fund: ' . ($portfolio->fund->name ?? 'N/A') . ')'];
        });

        return [
            'schedules' => $schedules,
            'funds' => $funds,
            'portfolios' => $portfolios,
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
        DB::commit();

        Log::info('Errors: ' . json_encode($errors));

        return view('scheduled_jobs.preview')
            ->with('scheduledJob', $scheduledJob)
            ->with('data', $data)
            ->with('children', $children)
            ->with('asOf', $asOf)
            ->with('shouldRunBy', $shouldRunBy)
            ->withErrors(new MessageBag($errors));
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
        } else if ($scheduledJob->entity_descr == ScheduledJobExt::ENTITY_PORTFOLIO_REPORT) {
            $children = PortfolioReportExt::query()
                ->where('scheduled_job_id', $scheduledJob->id)
                ->get();
        }
        return $children;
    }
}
