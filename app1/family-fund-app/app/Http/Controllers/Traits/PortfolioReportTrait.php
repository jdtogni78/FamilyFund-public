<?php

namespace App\Http\Controllers\Traits;

use App\Jobs\SendPortfolioReport;
use App\Mail\PortfolioReportEmail;
use App\Models\PortfolioExt;
use App\Models\PortfolioReportExt;
use App\Models\ScheduledJob;
use App\Repositories\PortfolioReportRepository;
use App\Repositories\PortfolioRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

trait PortfolioReportTrait
{
    use MailTrait;
    use VerboseTrait;

    public function sendPortfolioReport($portfolioReport)
    {
        $portfolio = $portfolioReport->portfolio;
        $start = Carbon::parse($portfolioReport->start_date);
        $end = Carbon::parse($portfolioReport->end_date);

        // Get the rebalance response data
        $portfolioRepo = App::make(PortfolioRepository::class);
        $portfolioController = new \App\Http\Controllers\WebV1\PortfolioControllerExt($portfolioRepo);

        $api = $portfolioController->createRebalanceResponse($portfolio, $start, $end);
        $api['asOf'] = $start;
        $api['endDate'] = $end;

        // Create the PDF
        $pdf = new PortfolioRebalancePDF($api, false);

        // Send the email
        $this->portfolioEmailReport($portfolioReport, $pdf);
    }

    protected function portfolioEmailReport(PortfolioReportExt $portfolioReport, PortfolioRebalancePDF $pdf): void
    {
        $portfolio = $portfolioReport->portfolio;
        $fund = $portfolio->fund;
        $dateRange = $portfolioReport->start_date->format('Y-m-d') . ' to ' . $portfolioReport->end_date->format('Y-m-d');

        $errs = [];
        $noEmail = [];
        $msgs = [];
        $sendCount = 0;

        // Get all accounts for the fund
        $accounts = $fund->accounts()->get();

        foreach ($accounts as $account) {
            $err = $account->validateHasEmail();
            if ($err != null) {
                $noEmail[] = $err;
            } else {
                $msg = "Sending portfolio rebalance report email to " . $account->email_cc;
                Log::info($msg);
                $msgs[] = $msg;

                $pdfFile = $pdf->file();
                $user = $account->user()->first();
                $reportData = new PortfolioReportEmail($portfolioReport, $user, $dateRange, $pdfFile);

                $sentMsg = $this->sendMail($reportData, $account->email_cc);
                if (null == $sentMsg) {
                    $sendCount++;
                } else {
                    $errs[] = $sentMsg;
                }
            }
        }

        if (count($noEmail)) {
            $errs[] = "No email for: " . implode(", ", $noEmail);
        }

        if ($sendCount == 0) {
            $msg = "No emails sent for portfolio report";
            Log::error($msg);
            $errs[] = $msg;
        }

        // Log results (using local vars to avoid trait property conflicts)
        if (count($errs)) {
            Log::warning("Portfolio report errors: " . implode(", ", $errs));
        }
        if (count($msgs)) {
            Log::info("Portfolio report messages: " . implode(", ", $msgs));
        }
    }

    protected function createPortfolioReport(array $input)
    {
        $portfolioReportRepo = App::make(PortfolioReportRepository::class);
        $portfolioReport = $portfolioReportRepo->create($input);
        SendPortfolioReport::dispatch($portfolioReport);
        return $portfolioReport;
    }

    protected function createPortfolioReportFromSchedule(mixed $job, $asOf, $shouldRunBy)
    {
        // Get the template report to find the portfolio and report type
        $templateReport = PortfolioReportExt::query()
            ->where('id', $job->entity_id)->first();

        if (!$templateReport) {
            Log::error("Portfolio report template not found for job entity_id: " . $job->entity_id);
            return null;
        }

        // Calculate date range based on report type
        $runDate = Carbon::parse($shouldRunBy);
        $reportType = $templateReport->report_type ?? PortfolioReportExt::TYPE_CUSTOM;
        [$startDate, $endDate] = PortfolioReportExt::calculateDateRange($reportType, $runDate);

        Log::info("Creating portfolio report: type={$reportType}, range={$startDate->toDateString()} to {$endDate->toDateString()}");

        $portfolioReport = $this->createPortfolioReport([
            'portfolio_id' => $templateReport->portfolio_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'report_type' => $reportType,
            'scheduled_job_id' => $job->id,
        ]);

        Log::info('Created portfolio report from schedule: ' . json_encode($portfolioReport));
        return $portfolioReport;
    }

    protected function portfolioReportScheduleDue($shouldRunBy, ScheduledJob $job, Carbon $asOf): ?PortfolioReportExt
    {
        return $this->createPortfolioReportFromSchedule($job, $asOf, $shouldRunBy);
    }
}
