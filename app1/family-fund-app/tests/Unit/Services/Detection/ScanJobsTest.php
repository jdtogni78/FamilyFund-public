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

        // Simulate that the cap (default=6) has already been reached
        $cacheKey = "credit_line_delay_count_{$payment->id}";
        Cache::put($cacheKey, 6, now()->addDays(365));

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

        $cacheKey = "credit_line_delay_count_{$payment->id}";
        $this->assertSame(1, (int) Cache::get($cacheKey));

        Mail::assertSent(DelayNotificationMail::class, function ($mail) use ($payment) {
            return $mail->payment->id === $payment->id && $mail->notificationCount === 1;
        });
    }
}
