<?php

namespace App\Jobs\CreditLine;

use App\Mail\CreditLine\DelayNotificationMail;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\CreditLinePayment;
use App\Services\CreditLine\Settings\LineNotificationSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * UC-19: Daily job that:
 *  1. Flips scheduled CreditLinePayment rows to `late` when grace period expires.
 *  2. Sends delay-notification emails, repeated every `delay_notification_repeat_days`
 *     until paid or the hard cap (default 6) is reached.
 *
 * Phase 2 TODO — register in console/Kernel.php:
 *   $schedule->job(new ScanLatePaymentsJob)->dailyAt('08:05');
 *
 * DEDUP strategy: uses a cache key per payment row to track how many notifications
 * have been sent. Format: "credit_line_delay_count_{payment_id}" → integer count.
 * Phase 2 may replace with a DB log table.
 */
class ScanLatePaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Carbon $today;

    public function __construct(?Carbon $today = null)
    {
        $this->today = $today ?? Carbon::today();
    }

    public function handle(): void
    {
        Log::info("ScanLatePaymentsJob: running for {$this->today->toDateString()}");

        // All scheduled or late payments on active lines past their due_date.
        $payments = CreditLinePayment::whereIn('status', [
                CreditLinePayment::STATUS_SCHEDULED,
                CreditLinePayment::STATUS_LATE,
            ])
            ->where('due_date', '<', $this->today->toDateString())
            ->with('creditLine.account')
            ->get();

        $flipped = 0;
        $notified = 0;

        foreach ($payments as $payment) {
            /** @var CreditLinePayment $payment */
            $line = $payment->creditLine;
            if ($line === null || $line->status !== AccountCreditLineExt::STATUS_ACTIVE) {
                continue;
            }

            $settings = LineNotificationSettings::for($line);
            $daysLate = $payment->due_date->diffInDays($this->today);

            // Step 1 — flip status to late once grace period expires.
            if ($payment->status === CreditLinePayment::STATUS_SCHEDULED
                && $daysLate >= $settings->delayNotificationGraceDays()
            ) {
                $payment->status = CreditLinePayment::STATUS_LATE;
                $payment->save();
                $flipped++;
                Log::info("ScanLatePaymentsJob: flipped payment #{$payment->id} to late ({$daysLate} days overdue)");
            }

            // Step 2 — send delay notification if enabled.
            if (!$settings->delayNotificationEnabled()) {
                continue;
            }
            if ($daysLate < $settings->delayNotificationGraceDays()) {
                continue;
            }

            $cacheKey    = "credit_line_delay_count_{$payment->id}";
            $sentCount   = (int) \Illuminate\Support\Facades\Cache::get($cacheKey, 0);
            $maxCount    = $settings->delayNotificationMax();
            $repeatDays  = $settings->delayNotificationRepeatDays();

            if ($sentCount >= $maxCount) {
                Log::debug("ScanLatePaymentsJob: hard cap reached for payment #{$payment->id} ({$sentCount}/{$maxCount})");
                continue;
            }

            // Is it time for another notification?
            // First notification: when daysLate == graceDays.
            // Subsequent: every repeatDays after that.
            $daysAfterGrace = $daysLate - $settings->delayNotificationGraceDays();
            $expectedCount  = (int) floor($daysAfterGrace / $repeatDays) + 1; // 1-based

            if ($expectedCount <= $sentCount) {
                // Not yet time for the next notification.
                continue;
            }

            $recipientEmail = $this->resolveEmail($line);
            if ($recipientEmail === null) {
                Log::warning("ScanLatePaymentsJob: no email for line #{$line->id}; skipping.");
                continue;
            }

            $newCount = $sentCount + 1;
            Mail::to($recipientEmail)->send(new DelayNotificationMail($line, $payment, $newCount));
            \Illuminate\Support\Facades\Cache::put($cacheKey, $newCount, now()->addDays(365));

            $notified++;
            Log::info("ScanLatePaymentsJob: sent delay notification #{$newCount} for payment #{$payment->id} to {$recipientEmail}");
        }

        Log::info("ScanLatePaymentsJob: done — flipped {$flipped}, notified {$notified}.");
    }

    private function resolveEmail(AccountCreditLine $line): ?string
    {
        $account = $line->account;
        return $account?->email_cc ?: null;
    }
}
