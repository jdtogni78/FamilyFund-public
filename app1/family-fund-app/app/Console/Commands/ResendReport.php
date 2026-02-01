<?php

namespace App\Console\Commands;

use App\Jobs\SendAccountReport;
use App\Jobs\SendFundReport;
use App\Models\AccountReport;
use App\Models\FundReport;
use App\Models\OperationLog;
use Illuminate\Console\Command;

class ResendReport extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'report:resend
                            {type : Type of report (fund or account)}
                            {id : The report ID}
                            {--dispatch : Also dispatch the job after clearing}';

    /**
     * The console command description.
     */
    protected $description = 'Clear completion records for a report to allow resending';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $id = (int) $this->argument('id');

        if (!in_array($type, ['fund', 'account'])) {
            $this->error("Invalid type. Must be 'fund' or 'account'.");
            return 1;
        }

        if ($type === 'fund') {
            return $this->handleFundReport($id);
        } else {
            return $this->handleAccountReport($id);
        }
    }

    protected function handleFundReport(int $id): int
    {
        $report = FundReport::find($id);
        if (!$report) {
            $this->error("FundReport #{$id} not found.");
            return 1;
        }

        $this->info("Fund Report #{$id}");
        $this->info("  Fund: {$report->fund->name}");
        $this->info("  Type: {$report->type}");
        $this->info("  As Of: {$report->as_of}");

        // Check current completion status
        $isCompleted = OperationLog::jobCompletedForModel(
            SendFundReport::class,
            FundReport::class,
            $id
        );

        if ($isCompleted) {
            $this->warn("  Status: COMPLETED (email already sent)");
        } else {
            $this->info("  Status: NOT COMPLETED");
        }

        // Clear completion records
        $deleted = OperationLog::clearJobCompletionForModel(FundReport::class, $id);
        $this->info("  Cleared {$deleted} completion record(s)");

        // Also clear related account reports if this is a fund report
        $accountReports = AccountReport::where('as_of', $report->as_of)->get();
        $totalAccountCleared = 0;
        foreach ($accountReports as $ar) {
            $totalAccountCleared += OperationLog::clearJobCompletionForModel(AccountReport::class, $ar->id);
        }
        if ($totalAccountCleared > 0) {
            $this->info("  Cleared {$totalAccountCleared} account report completion record(s)");
        }

        // Optionally dispatch
        if ($this->option('dispatch')) {
            SendFundReport::dispatch($report);
            $this->info("  Dispatched SendFundReport job");
        } else {
            $this->info("  Use --dispatch to also queue the job for sending");
        }

        return 0;
    }

    protected function handleAccountReport(int $id): int
    {
        $report = AccountReport::find($id);
        if (!$report) {
            $this->error("AccountReport #{$id} not found.");
            return 1;
        }

        $this->info("Account Report #{$id}");
        $this->info("  Account: {$report->account->nickname}");
        $this->info("  Type: {$report->type}");
        $this->info("  As Of: {$report->as_of}");

        // Check current completion status
        $isCompleted = OperationLog::jobCompletedForModel(
            SendAccountReport::class,
            AccountReport::class,
            $id
        );

        if ($isCompleted) {
            $this->warn("  Status: COMPLETED (email already sent)");
        } else {
            $this->info("  Status: NOT COMPLETED");
        }

        // Clear completion records
        $deleted = OperationLog::clearJobCompletionForModel(AccountReport::class, $id);
        $this->info("  Cleared {$deleted} completion record(s)");

        // Optionally dispatch
        if ($this->option('dispatch')) {
            SendAccountReport::dispatch($report);
            $this->info("  Dispatched SendAccountReport job");
        } else {
            $this->info("  Use --dispatch to also queue the job for sending");
        }

        return 0;
    }
}
