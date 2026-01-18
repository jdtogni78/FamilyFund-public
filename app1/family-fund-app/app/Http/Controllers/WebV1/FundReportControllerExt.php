<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\FundReportController;
use App\Http\Controllers\Traits\FundTrait;
use App\Http\Requests\CreateFundReportRequest;
use App\Http\Requests\UpdateFundReportRequest;
use App\Jobs\SendAccountReport;
use App\Jobs\SendFundReport;
use App\Models\AccountReport;
use App\Models\Fund;
use App\Models\FundReport;
use App\Models\FundReportExt;
use App\Models\OperationLog;
use App\Repositories\FundReportRepository;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Response;

class FundReportControllerExt extends FundReportController
{
    use FundTrait;

    public function __construct(FundReportRepository $fundReportRepo)
    {
        parent::__construct($fundReportRepo);
    }

    public function index(Request $request)
    {
        $fundReports = FundReportExt::with('fund')
            ->orderBy('as_of', 'desc')
            ->get();

        return view('fund_reports.index')
            ->with('fundReports', $fundReports);
    }

    public function show($id)
    {
        $fundReport = FundReportExt::with(['fund', 'scheduledJob.schedule'])->find($id);

        if (empty($fundReport)) {
            Flash::error('Fund Report not found');
            return redirect(route('fundReports.index'));
        }

        // Check email status from OperationLog
        $emailStatus = [
            'fundEmailSent' => OperationLog::jobCompletedForModel(
                SendFundReport::class,
                FundReport::class,
                $fundReport->id
            ),
            'accountReports' => [],
        ];

        // Get related account reports and their status
        $accountReports = AccountReport::where('as_of', $fundReport->as_of)->get();
        foreach ($accountReports as $ar) {
            $emailStatus['accountReports'][] = [
                'id' => $ar->id,
                'account' => $ar->account->nickname ?? 'N/A',
                'sent' => OperationLog::jobCompletedForModel(
                    SendAccountReport::class,
                    AccountReport::class,
                    $ar->id
                ),
            ];
        }

        return view('fund_reports.show')
            ->with('fundReport', $fundReport)
            ->with('emailStatus', $emailStatus);
    }

    public function create()
    {
        $api = [
            'typeMap' => FundReportExt::$typeMap,
            'funds' => Fund::orderBy('name')->pluck('name', 'id'),
        ];

        return view('fund_reports.create')
            ->with('api', $api);
    }

    public function edit($id)
    {
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            Flash::error('Fund Report not found');

            return redirect(route('fundReports.index'));
        }
        $api = [
            'typeMap' => FundReportExt::$typeMap,
            'funds' => Fund::orderBy('name')->pluck('name', 'id'),
        ];

        return view('fund_reports.edit')
            ->with('fundReport', $fundReport)
            ->with('api', $api);
    }

    public function store(CreateFundReportRequest $request)
    {
        try {
            $fundReport = $this->createFundReport($request->all());
            // createFundReport handles dispatch for non-templates
            if ($fundReport->as_of->format('Y-m-d') !== '9999-12-31') {
                Flash::success('Report created and queued for sending.');
            } else {
                Flash::success('Template saved successfully.');
            }
        } catch (Exception $e) {
            report($e);
            Flash::error($e->getMessage());
        }

        return redirect(route('fundReports.index'));
    }

    public function update($id, UpdateFundReportRequest $request)
    {
        $fundReport = $this->fundReportRepository->find($id);

        if (empty($fundReport)) {
            Flash::error('Fund Report not found');

            return redirect(route('fundReports.index'));
        }

        $fundReport = $this->fundReportRepository->update($request->all(), $id);
        // Only dispatch if not a template (9999-12-31)
        if ($fundReport->as_of->format('Y-m-d') !== '9999-12-31') {
            SendFundReport::dispatch($fundReport);
            Flash::success('Report updated and queued for sending.');
        } else {
            Flash::success('Template updated successfully.');
        }

        return redirect(route('fundReports.index'));
    }

    public function resend($id)
    {
        $fundReport = FundReportExt::find($id);

        if (empty($fundReport)) {
            Flash::error('Fund Report not found');
            return redirect(route('fundReports.index'));
        }

        if ($fundReport->as_of->format('Y-m-d') === '9999-12-31') {
            Flash::error('Cannot resend a template');
            return redirect(route('fundReports.index'));
        }

        // Clear completion records to allow resending
        $fundCleared = OperationLog::clearJobCompletionForModel(FundReport::class, $fundReport->id);

        // Also clear related account reports
        $accountCleared = 0;
        $accountReports = AccountReport::where('as_of', $fundReport->as_of)->get();
        foreach ($accountReports as $ar) {
            $accountCleared += OperationLog::clearJobCompletionForModel(AccountReport::class, $ar->id);
        }

        SendFundReport::dispatch($fundReport);

        $msg = "Report #{$id} queued for resending.";
        if ($fundCleared > 0 || $accountCleared > 0) {
            $msg .= " Cleared {$fundCleared} fund + {$accountCleared} account completion record(s).";
        }
        Flash::success($msg);

        return redirect(route('fundReports.show', $id));
    }

}
