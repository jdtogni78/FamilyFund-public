<?php

namespace App\Jobs\CreditLine;

use App\Mail\CreditLine\StatusUpdateMail;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\AccountExt;
use App\Services\CreditLine\Reporting\LoansSummaryBuilder;
use App\Services\CreditLine\Reporting\TrajectoryBuilder;
use App\Services\CreditLine\Settings\LineNotificationSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * UC-50: Quarterly per-account credit-line status digest.
 *
 * Enumerates every account with at least one active credit line and sends a
 * single digest email per account: the LoansSummaryBuilder snapshot plus a
 * trajectory-only forecast (TrajectoryBuilder) for each active line whose
 * `status_update_enabled` setting is on.
 *
 * Registered in console/Kernel.php to run quarterly. Idempotent within a
 * quarter via a per-account cache key, so a same-quarter re-dispatch (retry,
 * manual run) will not double-send.
 */
class SendStatusUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Carbon $today;

    public function __construct(?Carbon $today = null)
    {
        // Accept an injectable date for testability.
        $this->today = $today ?? Carbon::today();
    }

    public function handle(
        ?LoansSummaryBuilder $summaryBuilder = null,
        ?TrajectoryBuilder $trajectoryBuilder = null
    ): void {
        $summaryBuilder    ??= new LoansSummaryBuilder();
        $trajectoryBuilder ??= new TrajectoryBuilder();

        $quarter = 'Q' . (int) ceil($this->today->month / 3) . '-' . $this->today->year;
        Log::info("SendStatusUpdateJob: running for {$this->today->toDateString()} ({$quarter})");

        $accountIds = AccountCreditLine::where('status', AccountCreditLineExt::STATUS_ACTIVE)
            ->distinct()
            ->pluck('account_id');

        $sent = 0;

        foreach ($accountIds as $accountId) {
            $account = AccountExt::find($accountId);
            if ($account === null) {
                continue;
            }

            // Per-quarter dedup: claim the slot atomically; skip if already sent.
            $cacheKey = "credit_line_status_update_{$accountId}_{$quarter}";
            if (!Cache::add($cacheKey, 1, now()->addDays(95))) {
                Log::debug("SendStatusUpdateJob: already sent for account #{$accountId} this quarter; skipping.");
                continue;
            }

            $recipientEmail = $account->email_cc ?: null;
            if ($recipientEmail === null) {
                Log::warning("SendStatusUpdateJob: no email for account #{$accountId}; skipping.");
                continue;
            }

            $activeLines = AccountCreditLine::where('account_id', $accountId)
                ->where('status', AccountCreditLineExt::STATUS_ACTIVE)
                ->orderBy('id')
                ->get();

            $lineEntries = [];
            foreach ($activeLines as $line) {
                if (!LineNotificationSettings::for($line)->statusUpdateEnabled()) {
                    continue;
                }
                $lineEntries[] = [
                    'line'       => $line,
                    'trajectory' => $trajectoryBuilder->build($line),
                ];
            }

            if (empty($lineEntries)) {
                Log::debug("SendStatusUpdateJob: account #{$accountId} has no status-update-enabled lines; skipping.");
                continue;
            }

            $summary = $summaryBuilder->forAccount($account);

            Mail::to($recipientEmail)->send(new StatusUpdateMail(
                $account,
                $summary,
                $lineEntries,
                $this->today->toDateString()
            ));
            $sent++;
            Log::info("SendStatusUpdateJob: sent digest for account #{$accountId} to {$recipientEmail} ({$summary['active_line_count']} active line(s))");
        }

        Log::info("SendStatusUpdateJob: done — sent {$sent} digest(s).");
    }
}
