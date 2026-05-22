<?php

namespace Tests\Feature\Scenarios;

use App\Jobs\CreditLine\ScanLatePaymentsJob;
use App\Mail\CreditLine\DelayNotificationMail;
use App\Models\CreditLineDelayNotification;
use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S9 — Delay-notification dedup across repeat runs.
 *
 * Locks in the persisted-ledger dedup introduced by commit 1e6bdc2b: the
 * job derives `sentCount` from CreditLineDelayNotification rows (not the
 * cache), and a unique (credit_line_payment_id, notification_number) index
 * makes concurrent double-sends safely fail at the DB level.
 *
 * Backdates a line so its first row is past the 3-day grace period, then
 * runs ScanLatePaymentsJob twice on the same date. Asserts exactly one
 * ledger row exists for *that payment* and exactly one DelayNotificationMail
 * matching that payment was queued.
 *
 * NOTE: PHPUnit feature tests in this repo run against the shared dev DB
 * (no per-test schema). Pre-existing late payments on other accounts will
 * also trigger the job, so every assertion here is scoped to the payment
 * created in setUp — never on global counts.
 */
class S9DelayNotificationDedupTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    public function test_running_scan_twice_same_day_sends_exactly_one_delay_notification(): void
    {
        $this->s->withBorrowingPower('bI', 500);

        // 30-day backdated draw → first row is ~30 days past due, well over
        // the 3-day default grace period.
        $line = $this->s->openLine('bI', 60, 6, 'monthly', Carbon::today()->subDays(45), 'S9 dedup');

        // resolveEmail() reads $account->email_cc; without it the job logs
        // and skips, so set it here.
        $account = $this->s->account('bI');
        $account->email_cc = 'recipient@example.test';
        $account->save();

        $firstRow = $this->s->rows($line)->first();

        // First scan: one ledger row + one email for THIS payment.
        (new ScanLatePaymentsJob(Carbon::today()))->handle();

        $this->assertSame(
            1,
            CreditLineDelayNotification::where('credit_line_payment_id', $firstRow->id)->count(),
            'first scan must record exactly one ledger row for this payment'
        );
        $this->assertSame(
            1,
            $this->sentMailCountForPayment($firstRow->id),
            'first scan must dispatch exactly one DelayNotificationMail for this payment'
        );

        // Second scan, same date: dedup kicks in for THIS payment.
        (new ScanLatePaymentsJob(Carbon::today()))->handle();

        $this->assertSame(
            1,
            CreditLineDelayNotification::where('credit_line_payment_id', $firstRow->id)->count(),
            'second same-day scan must not insert a duplicate ledger row for this payment'
        );
        $this->assertSame(
            1,
            $this->sentMailCountForPayment($firstRow->id),
            'second same-day scan must not re-send the notification for this payment'
        );
    }

    public function test_disabling_delay_notifications_on_line_suppresses_all_sends(): void
    {
        $this->s->withBorrowingPower('bI', 500);
        $line = $this->s->openLine('bI', 60, 6, 'monthly', Carbon::today()->subDays(45), 'S9 disabled');

        $account = $this->s->account('bI');
        $account->email_cc = 'recipient@example.test';
        $account->save();

        // Operator opts the line out of delay notifications.
        $line->delay_notification_enabled = false;
        $line->save();
        $line->refresh();

        $firstRow = $this->s->rows($line)->first();

        (new ScanLatePaymentsJob(Carbon::today()))->handle();
        (new ScanLatePaymentsJob(Carbon::today()))->handle();

        $this->assertSame(
            0,
            CreditLineDelayNotification::where('credit_line_payment_id', $firstRow->id)->count(),
            'no ledger rows must be written when delay notifications are disabled'
        );
        $this->assertSame(
            0,
            $this->sentMailCountForPayment($firstRow->id),
            'no DelayNotificationMail must be dispatched for this payment when disabled'
        );
    }

    public function test_subsequent_notification_fires_after_repeat_days_elapse(): void
    {
        $this->s->withBorrowingPower('bI', 500);

        // 50-day backdate: first monthly row's due_date is ~20 days past
        // today (origination + 1 month is in the past), well past the 3-day
        // grace. Notification #1 fires now; #2 should fire 14 days later
        // (the default repeat cadence).
        $line = $this->s->openLine('bI', 60, 6, 'monthly', Carbon::today()->subDays(50), 'S9 cadence');

        $account = $this->s->account('bI');
        $account->email_cc = 'recipient@example.test';
        $account->save();

        $firstRow = $this->s->rows($line)->first();
        // Force the row late so the job's notification path runs (the job
        // only sends when status is SCHEDULED or LATE; if DrawService already
        // flipped it to LATE, this is a no-op).
        if ($firstRow->status === CreditLinePayment::STATUS_SCHEDULED) {
            $firstRow->status = CreditLinePayment::STATUS_LATE;
            $firstRow->save();
        }

        (new ScanLatePaymentsJob(Carbon::today()))->handle();
        $this->assertSame(1, CreditLineDelayNotification::where('credit_line_payment_id', $firstRow->id)->count());
        $this->assertSame(1, $this->sentMailCountForPayment($firstRow->id));

        // 14 days later — notification #2 should fire.
        (new ScanLatePaymentsJob(Carbon::today()->addDays(14)))->handle();

        $this->assertSame(
            2,
            CreditLineDelayNotification::where('credit_line_payment_id', $firstRow->id)->count(),
            'a second notification must fire once delay_notification_repeat_days elapses'
        );
        $this->assertSame(
            2,
            $this->sentMailCountForPayment($firstRow->id),
            'exactly two DelayNotificationMail must be dispatched for this payment'
        );
    }

    private function sentMailCountForPayment(int $paymentId): int
    {
        return Mail::sent(DelayNotificationMail::class, function ($mail) use ($paymentId) {
            return (int) $mail->payment->id === $paymentId;
        })->count();
    }
}
