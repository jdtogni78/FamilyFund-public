<?php

namespace App\Http\Controllers\Traits;

use App\Jobs\SendTradeBandReport;
use App\Models\FundExt;
use App\Models\ScheduledJob;
use App\Models\TradeBandReport;
use App\Repositories\TradeBandReportRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

trait TradeBandReportTrait
{
    use MailTrait;
    use VerboseTrait;

    public function sendTradeBandReport(TradeBandReport $tradeBandReport)
    {
        $fund = $tradeBandReport->fund;
        $asOf = $tradeBandReport->as_of->format('Y-m-d');

        // Generate the trading bands response and PDF
        $arr = $this->createFundResponseTradeBands($fund, $asOf, true);
        $pdf = new FundPDF();
        $pdf->createTradeBandsPDF($arr, true);

        // Send to fund admin
        $this->tradeBandEmailReport($tradeBandReport, $pdf);
    }

    protected function tradeBandEmailReport(TradeBandReport $tradeBandReport, FundPDF $pdf): void
    {
        $fund = $tradeBandReport->fund;
        $asOf = $tradeBandReport->as_of->format('Y-m-d');

        $errs = [];
        $noEmail = [];
        $msgs = [];
        $sendCount = 0;

        // Send to fund admin accounts (accounts with no users)
        $accounts = $fund->accounts()->get();
        foreach ($accounts as $account) {
            $users = $account->user()->get();
            // Only send to accounts with no users (fund admin accounts)
            if (count($users) == 0) {
                $err = $account->validateHasEmail();
                if ($err != null) {
                    $noEmail[] = $err;
                } else {
                    $msg = "Sending trade band report email to " . $account->email_cc;
                    Log::info($msg);
                    $msgs[] = $msg;

                    $pdfFile = $pdf->file();
                    $reportData = new \App\Mail\TradeBandReportEmail($tradeBandReport, $asOf, $pdfFile);

                    $sentMsg = $this->sendMail($reportData, $account->email_cc);
                    if (null == $sentMsg) {
                        $sendCount++;
                    } else {
                        $errs[] = $sentMsg;
                    }
                }
            }
        }

        if (count($noEmail)) {
            $errs[] = "No email for: " . implode(", ", $noEmail);
        }

        if ($sendCount == 0) {
            $msg = "No emails sent for trade band report";
            Log::error($msg);
            $errs[] = $msg;
        }

        if (count($errs)) {
            Log::warning("Trade band report errors: " . implode(", ", $errs));
        }
        if (count($msgs)) {
            Log::info("Trade band report messages: " . implode(", ", $msgs));
        }
    }

    protected function createTradeBandReport(array $input): TradeBandReport
    {
        $tradeBandReportRepo = App::make(TradeBandReportRepository::class);
        $tradeBandReport = $tradeBandReportRepo->create($input);
        SendTradeBandReport::dispatch($tradeBandReport);
        return $tradeBandReport;
    }

    protected function tradeBandReportScheduleDue($shouldRunBy, ScheduledJob $job, Carbon $asOf): ?TradeBandReport
    {
        // entity_id is the Fund ID for trade_band_report scheduled jobs
        $fund = FundExt::find($job->entity_id);

        if (!$fund) {
            Log::error("Fund not found for scheduled job entity_id: " . $job->entity_id);
            return null;
        }

        Log::info("Creating trade band report for fund {$fund->id} as of {$shouldRunBy->toDateString()}");

        $tradeBandReport = $this->createTradeBandReport([
            'fund_id' => $fund->id,
            'as_of' => $shouldRunBy,
            'scheduled_job_id' => $job->id,
        ]);

        Log::info('Created trade band report from schedule: ' . json_encode($tradeBandReport));
        return $tradeBandReport;
    }
}
