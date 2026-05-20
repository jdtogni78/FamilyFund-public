<?php

namespace Tests\Unit\Services\Detection;

use App\Jobs\CreditLine\ScanLatePaymentsJob;
use App\Jobs\CreditLine\ScanRemindersJob;
use App\Mail\CreditLine\DelayNotificationMail;
use App\Mail\CreditLine\ReminderMail;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

class ScanJobsTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed CASH asset (required by DataFactory::createFund)
        \App\Models\Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeActiveLine(): AccountCreditLine
    {
        $account = $this->factory->userAccount;
        $account->email_cc = 'test@example.com';
        $account->save();

        return AccountCreditLine::create([
            'account_id'         => $account->id,
            'principal_shares'   => 10,
            'outstanding_shares' => 10,
            'term_months'        => 12,
            'origination_date'   => Carbon::today()->subMonths(1)->toDateString(),
            'maturity_date'      => Carbon::today()->addMonths(11)->toDateString(),
            'payment_frequency'  => AccountCreditLineExt::FREQUENCY_MONTHLY,
            'status'             => AccountCreditLineExt::STATUS_ACTIVE,
        ]);
    }

    private function makePayment(AccountCreditLine $line, string $dueDate, string $status = CreditLinePayment::STATUS_SCHEDULED): CreditLinePayment
    {
        return CreditLinePayment::create([
            'account_credit_line_id' => $line->id,
            'due_date'               => $dueDate,
            'shares_due'             => 1,
            'status'                 => $status,
        ]);
    }

    // ── ScanRemindersJob ──────────────────────────────────────────────────────

    public function test_scan_reminders_sends_reminder_when_lead_days_match(): void
    {
        Mail::fake();

        $line    = $this->makeActiveLine();
        $dueDate = Carbon::today()->addDays(7)->toDateString(); // exactly leadDays (default=7) away
        $payment = $this->makePayment($line, $dueDate);

        (new ScanRemindersJob(Carbon::today()))->handle();

        // Scope to this test's own payment: the job scans the whole table, and a
        // populated (e.g. cloned-from-dev) DB may contain unrelated due payments.
        Mail::assertSent(ReminderMail::class, fn ($m) => $m->payment->id === $payment->id);
    }

    public function test_scan_reminders_does_not_send_when_not_lead_day(): void
    {
        Mail::fake();

        $line    = $this->makeActiveLine();
        $dueDate = Carbon::today()->addDays(5)->toDateString(); // 5 days — not the default lead of 7
        $payment = $this->makePayment($line, $dueDate);

        (new ScanRemindersJob(Carbon::today()))->handle();

        Mail::assertNotSent(ReminderMail::class, fn ($m) => $m->payment->id === $payment->id);
    }

    // ── ScanLatePaymentsJob ───────────────────────────────────────────────────

    public function test_scan_late_payments_flips_status_to_late_and_sends_notification(): void
    {
        Mail::fake();
        Cache::flush();

        $line    = $this->makeActiveLine();
        // Due 3 days ago (= grace days default=3, so today is exactly at grace boundary)
        $dueDate = Carbon::today()->subDays(3)->toDateString();
        $payment = $this->makePayment($line, $dueDate, CreditLinePayment::STATUS_SCHEDULED);

        (new ScanLatePaymentsJob(Carbon::today()))->handle();

        $payment->refresh();
        $this->assertSame(CreditLinePayment::STATUS_LATE, $payment->status);

        Mail::assertSent(DelayNotificationMail::class, fn ($m) => $m->payment->id === $payment->id);
    }

    public function test_scan_late_payments_respects_repeat_cap(): void
    {
        Mail::fake();
        Cache::flush();

        $line    = $this->makeActiveLine();
        $dueDate = Carbon::today()->subDays(3)->toDateString();
        $payment = $this->makePayment($line, $dueDate, CreditLinePayment::STATUS_LATE);

        // Simulate that the cap (default=6) has already been reached.
        for ($i = 1; $i <= 6; $i++) {
            \App\Models\CreditLineDelayNotification::create([
                'credit_line_payment_id' => $payment->id,
                'notification_number'    => $i,
                'sent_at'                => now()->subDays(7 * (6 - $i)),
                'recipient_email'        => 'test@example.com',
            ]);
        }

        (new ScanLatePaymentsJob(Carbon::today()))->handle();

        // No additional email should be sent for this capped payment. Scope to it:
        // the job scans the whole table and a populated DB may hold other late rows.
        Mail::assertNotSent(DelayNotificationMail::class, fn ($m) => $m->payment->id === $payment->id);
    }

    public function test_scan_late_payments_increments_notification_count(): void
    {
        Mail::fake();
        Cache::flush();

        $line    = $this->makeActiveLine();
        $dueDate = Carbon::today()->subDays(3)->toDateString();
        $payment = $this->makePayment($line, $dueDate, CreditLinePayment::STATUS_LATE);

        // Send the first notification
        (new ScanLatePaymentsJob(Carbon::today()))->handle();

        $this->assertSame(
            1,
            \App\Models\CreditLineDelayNotification::where('credit_line_payment_id', $payment->id)->count()
        );

        Mail::assertSent(DelayNotificationMail::class, function ($mail) use ($payment) {
            return $mail->payment->id === $payment->id && $mail->notificationCount === 1;
        });
    }

    // ── Issue #7: notification counter must survive cache flush ───────────────

    /**
     * If the cache is evicted between runs the job must NOT re-send a
     * notification it has already sent. The DB ledger is the unit of truth.
     */
    public function test_scan_late_payments_does_not_resend_after_cache_flush(): void
    {
        Mail::fake();
        Cache::flush();

        $line    = $this->makeActiveLine();
        // Use a single date so the expected/sent counts stay aligned. With
        // a 3-day grace + 7-day repeat, day-3 is the first notification.
        $today   = Carbon::today();
        $dueDate = $today->copy()->subDays(3)->toDateString();
        $payment = $this->makePayment($line, $dueDate, CreditLinePayment::STATUS_LATE);

        // First run sends notification #1.
        (new ScanLatePaymentsJob($today))->handle();
        Mail::assertSent(DelayNotificationMail::class, fn ($m) => $m->payment->id === $payment->id);

        // Simulate cache eviction / multi-worker fresh state.
        Cache::flush();

        // Re-run on the same day. Without a DB ledger the cache-flushed
        // counter resets to 0 and the job re-sends notification #1.
        Mail::fake();
        (new ScanLatePaymentsJob($today))->handle();
        Mail::assertNotSent(DelayNotificationMail::class, fn ($m) => $m->payment->id === $payment->id);
    }
}
