<?php

namespace App\Http\Controllers\Traits;

use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use App\Models\Fund;
use App\Mail\ScheduledJobFailureMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait ScheduledJobEmailAlertTrait
{
    /**
     * Send email alert when a scheduled job fails
     *
     * @param ScheduledJob $job The scheduled job that failed
     * @param Carbon $asOf The date being processed
     * @param string $reason Failure reason
     * @param \Exception|null $exception Optional exception if one was thrown
     * @param array $context Additional context information
     */
    protected function sendScheduledJobFailureAlert(
        ScheduledJob $job,
        Carbon $asOf,
        string $reason,
        ?\Exception $exception = null,
        array $context = []
    ): void {
        try {
            // Get entity details
            $entityType = ScheduledJobExt::$entityMap[$job->entity_descr] ?? $job->entity_descr;
            $entityId = $job->entity_id;

            // Get entity name based on type
            $entityName = $this->getEntityName($job);

            // Calculate how overdue the job is
            $shouldRunBy = $job->shouldRunBy($asOf);
            $shouldRunByDate = $shouldRunBy['shouldRunBy'];
            $daysOverdue = $shouldRunByDate->diffInDays($asOf);

            // Get recommended actions
            $recommendedActions = $this->getRecommendedActions($job, $reason, $daysOverdue);

            // Send email using Laravel Mail
            $mail = new ScheduledJobFailureMail(
                $job,
                $asOf,
                $reason,
                $exception,
                $entityType,
                $entityName,
                $shouldRunByDate,
                $daysOverdue,
                $context,
                $recommendedActions
            );

            Mail::to('admin@dev.familyfund.local')->send($mail); // TODO: Make this configurable

            Log::info("Sent scheduled job failure alert for job {$job->id}");

        } catch (\Exception $e) {
            // Don't let email failure break the job processing
            Log::error("Failed to send scheduled job failure alert: " . $e->getMessage());
            report($e);
        }
    }

    /**
     * Get human-readable entity name
     */
    private function getEntityName(ScheduledJob $job): string
    {
        switch ($job->entity_descr) {
            case ScheduledJobExt::ENTITY_FUND_REPORT:
                $fund = Fund::find($job->entity_id);
                return $fund ? $fund->name : "Fund ID {$job->entity_id}";

            case ScheduledJobExt::ENTITY_TRANSACTION:
                return "Transaction ID {$job->entity_id}";

            case ScheduledJobExt::ENTITY_TRADE_BAND_REPORT:
                return "Trading Bands ID {$job->entity_id}";

            case ScheduledJobExt::ENTITY_MATCHING_REMINDER:
                return "Matching Reminder ID {$job->entity_id}";

            case ScheduledJobExt::ENTITY_HOLIDAYS_SYNC:
                return "Holiday Sync";

            default:
                return "Unknown ID {$job->entity_id}";
        }
    }

    /**
     * Get recommended actions based on failure type
     */
    private function getRecommendedActions(ScheduledJob $job, string $reason, int $daysOverdue): string
    {
        $actions = [];

        if ($job->entity_descr === ScheduledJobExt::ENTITY_FUND_REPORT) {
            if (strpos($reason, 'No data') !== false) {
                $actions[] = "1. Check asset price gaps: GET /api/asset_prices/gaps?days=7";
                $actions[] = "2. Backfill missing asset prices using DSTrader history mode";
                $actions[] = "3. Re-run the job after data is populated";
                $actions[] = "4. Or force-run with skip_data_check=true if report is urgently needed";
            }
        }

        if ($daysOverdue > 4) {
            $actions[] = "CRITICAL: This job is more than 4 days overdue. Immediate action required!";
        }

        if (empty($actions)) {
            $actions[] = "Review the error details above and take appropriate action.";
            $actions[] = "You can force-run the job using the API endpoints listed below.";
        }

        return implode("\n", $actions);
    }
}
