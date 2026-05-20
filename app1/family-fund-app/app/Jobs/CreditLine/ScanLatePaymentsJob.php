<?php

namespace App\Jobs\CreditLine;

use App\Mail\CreditLine\DelayNotificationMail;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\CreditLineDelayNotification;
use App\Models\CreditLinePayment;
use App\Services\CreditLine\Settings\LineNotificationSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
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
 * DEDUP strategy: each sent notification is recorded in
 * `credit_line_delay_notifications` (one row per email). The job derives
 * `sentCount` from that table, so the counter survives cache flushes and
 * stays consistent across multiple queue workers. The unique
 * (credit_line_payment_id, notification_number) index makes a concurrent
 * double-send safely fail at the DB level (Issue #7).
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

            $sentCount   = CreditLineDelayNotification::where('credit_line_payment_id', $payment->id)->count();
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

            // Insert the ledger row FIRST. If another worker raced us to this
            // notification slot, the unique index throws and we skip sending
            // — preferring under-notify to duplicate emails.
            try {
                CreditLineDelayNotification::create([
                    'credit_line_payment_id' => $payment->id,
                    'notification_number'    => $newCount,
                    'sent_at'                => now(),
                    'recipient_email'        => $recipientEmail,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                Log::info("ScanLatePaymentsJob: notification #{$newCount} for payment #{$payment->id} already claimed; skipping send.");
                continue;
            }

            Mail::to($recipientEmail)->send(new DelayNotificationMail($line, $payment, $newCount));

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
