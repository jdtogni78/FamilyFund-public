<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateScheduledJobRequest;
use App\Http\Requests\UpdateScheduledJobRequest;
use App\Models\FundReport;
use App\Models\TradeBandReport;
use App\Models\Transaction;
use App\Models\Schedule;
use App\Repositories\ScheduledJobRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class ScheduledJobController extends AppBaseController
{
    /** @var ScheduledJobRepository $scheduledJobRepository*/
    public $scheduledJobRepository;

    public function __construct(ScheduledJobRepository $scheduledJobRepo)
    {
        $this->scheduledJobRepository = $scheduledJobRepo;
    }

    /**
     * Display a listing of the ScheduledJob.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $scheduledJobs = $this->scheduledJobRepository->all();

        return view('scheduled_jobs.index')
            ->with('scheduledJobs', $scheduledJobs);
    }

    /**
     * Show the form for creating a new ScheduledJob.
     *
     * @return Response
     */
    public function create()
    {
        return view('scheduled_jobs.create', $this->getFormData());
    }

    /**
     * Get form data for create/edit views.
     */
    private function getFormData(): array
    {
        return [
            'schedules' => Schedule::pluck('descr', 'id'),
            'fundReportTemplates' => FundReport::where('as_of', '9999-12-31')
                ->with('fund')
                ->get()
                ->mapWithKeys(fn($r) => [$r->id => $r->fund->name . ' - ' . $r->type]),
            'tradeBandReportTemplates' => TradeBandReport::where('as_of', '9999-12-31')
                ->with('fund')
                ->get()
                ->mapWithKeys(fn($r) => [$r->id => $r->fund->name]),
            'transactionTemplates' => Transaction::whereDate('timestamp', '9999-12-31')
                ->with('account')
                ->get()
                ->mapWithKeys(fn($t) => [$t->id => ($t->account->nickname ?? 'Acct#'.$t->account_id) . ' - ' . $t->type]),
        ];
    }

    /**
     * Store a newly created ScheduledJob in storage.
     *
     * @param CreateScheduledJobRequest $request
     *
     * @return Response
     */
    public function store(CreateScheduledJobRequest $request)
    {
        $input = $request->all();

        $scheduledJob = $this->scheduledJobRepository->create($input);

        Flash::success('Scheduled Job saved successfully.');

        return redirect(route('scheduledJobs.index'));
    }

    /**
     * Display the specified ScheduledJob.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $scheduledJob = $this->scheduledJobRepository->find($id);

        if (empty($scheduledJob)) {
            Flash::error('Scheduled Job not found');

            return redirect(route('scheduledJobs.index'));
        }

        return view('scheduled_jobs.show')->with('scheduledJob', $scheduledJob);
    }

    /**
     * Show the form for editing the specified ScheduledJob.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $scheduledJob = $this->scheduledJobRepository->find($id);

        if (empty($scheduledJob)) {
            Flash::error('Scheduled Job not found');

            return redirect(route('scheduledJobs.index'));
        }

        return view('scheduled_jobs.edit', array_merge(
            ['scheduledJob' => $scheduledJob],
            $this->getFormData()
        ));
    }

    /**
     * Update the specified ScheduledJob in storage.
     *
     * @param int $id
     * @param UpdateScheduledJobRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateScheduledJobRequest $request)
    {
        $scheduledJob = $this->scheduledJobRepository->find($id);

        if (empty($scheduledJob)) {
            Flash::error('Scheduled Job not found');

            return redirect(route('scheduledJobs.index'));
        }

        $scheduledJob = $this->scheduledJobRepository->update($request->all(), $id);

        Flash::success('Scheduled Job updated successfully.');

        return redirect(route('scheduledJobs.index'));
    }

    /**
     * Remove the specified ScheduledJob from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $scheduledJob = $this->scheduledJobRepository->find($id);

        if (empty($scheduledJob)) {
            Flash::error('Scheduled Job not found');

            return redirect(route('scheduledJobs.index'));
        }

        $this->scheduledJobRepository->delete($id);

        Flash::success('Scheduled Job deleted successfully.');

        return redirect(route('scheduledJobs.index'));
    }
}
