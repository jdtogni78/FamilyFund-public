<?php

namespace App\Jobs\CreditLine;

use App\Mail\CreditLine\ReminderMail;
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
 * UC-18: Daily job that scans active CreditLinePayment rows and sends reminder
 * emails to account owners when due_date is exactly `reminder_lead_days` away.
 *
 * Phase 2 TODO — register in console/Kernel.php:
 *   $schedule->job(new ScanRemindersJob)->dailyAt('08:00');
 */
class ScanRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Carbon $today;

    public function __construct(?Carbon $today = null)
    {
        // Accept an injectable date for testability.
        $this->today = $today ?? Carbon::today();
    }

    public function handle(): void
    {
        Log::info("ScanRemindersJob: running for {$this->today->toDateString()}");

        // Load all scheduled payments on active lines, eager-load the credit line.
        $payments = CreditLinePayment::where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->with('creditLine.account')
            ->get();

        $sent = 0;

        foreach ($payments as $payment) {
            /** @var CreditLinePayment $payment */
            $line = $payment->creditLine;
            if ($line === null || $line->status !== AccountCreditLineExt::STATUS_ACTIVE) {
                continue;
            }

            $settings   = LineNotificationSettings::for($line);
            if (!$settings->reminderEnabled()) {
                continue;
            }

            $leadDays = $settings->reminderLeadDays();
            $targetDate = $this->today->copy()->addDays($leadDays);

            if ($payment->due_date->toDateString() !== $targetDate->toDateString()) {
                continue;
            }

            $recipientEmail = $this->resolveEmail($line);
            if ($recipientEmail === null) {
                Log::warning("ScanRemindersJob: no email for line #{$line->id}; skipping.");
                continue;
            }

            Mail::to($recipientEmail)->send(new ReminderMail($line, $payment, $leadDays));
            $sent++;
            Log::info("ScanRemindersJob: sent reminder for payment #{$payment->id} (due {$payment->due_date->toDateString()}) to {$recipientEmail}");
        }

        Log::info("ScanRemindersJob: done — sent {$sent} reminder(s).");
    }

    private function resolveEmail(AccountCreditLine $line): ?string
    {
        $account = $line->account;
        return $account?->email_cc ?: null;
    }
}
