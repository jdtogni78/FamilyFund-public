<?php

namespace Tests\Feature\Scenarios;

use App\Models\AccountCreditLineExt;
use App\Models\CreditLineAdjustment;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Models\UserExt;
use App\Services\CreditLine\Adjust\ReadjustService;
use App\Services\CreditLine\Reverse\ReverseService;
use App\Services\CreditLine\Reverse\ReversalOutstandingRecomputer;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S6 — Readjustment + reversed REP.
 *
 * Opens a line, makes two repayments, then mid-life:
 *   1. Readjust 12 → 18 months → fresh schedule, prior `scheduled` rows
 *      cancelled, paid rows preserved, one CreditLineAdjustment audit row.
 *   2. Reverse one REP through ReverseService → outstanding restored, the
 *      REP's allocations deleted, line stays active (no auto-flip to
 *      paid_off on reversal), transaction row kept with reversed=true.
 *
 * Exercises the full reverse pipeline (not just the flag-flip), so the
 * paired re-opening of `paid` rows and the OutstandingCalculator filter on
 * `reversed=true` are both verified end-to-end.
 */
class S6ReadjustReverseTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    public function test_readjust_writes_audit_row_cancels_scheduled_and_keeps_paid_rows(): void
    {
        $this->s->withBorrowingPower('bF', 500);
        $line = $this->s->openLine('bF', 120, 12, 'monthly', Carbon::today()->subMonths(3), 'S6: pre-readjust');

        // Two REPs land on the first two scheduled rows.
        $this->s->repayRow($line, 0, 10.0, Carbon::today()->subMonths(2));
        $this->s->repayRow($line, 1, 10.0, Carbon::today()->subMonth());

        $auditBefore = CreditLineAdjustment::where('account_credit_line_id', $line->id)->count();
        $paidBefore  = $line->payments()->where('status', CreditLinePayment::STATUS_PAID)->count();

        // 12 → 18 months. Frequency unchanged.
        $admin = UserExt::find($this->s->admin->id);
        $service = new ReadjustService(new OutstandingCalculator(), new AmortizationScheduleBuilder());
        $service->readjust($line->refresh(), 18, null, $admin, 'S6 term extension');

        $line->refresh();
        $this->assertSame(18, (int) $line->term_months);
        $this->assertSame(
            $auditBefore + 1,
            CreditLineAdjustment::where('account_credit_line_id', $line->id)->count(),
            'readjust must append exactly one audit row'
        );

        // Paid rows survive.
        $paidAfter = $line->payments()->where('status', CreditLinePayment::STATUS_PAID)->count();
        $this->assertSame($paidBefore, $paidAfter, 'paid rows must be preserved across readjust');

        // Prior `scheduled` rows are now `cancelled`; fresh schedule exists.
        $this->assertGreaterThan(0, $line->payments()->where('status', CreditLinePayment::STATUS_CANCELLED)->count());
        $this->assertGreaterThan(0, $line->payments()->where('status', CreditLinePayment::STATUS_SCHEDULED)->count());
    }

    public function test_reverse_rep_restores_outstanding_and_keeps_line_active(): void
    {
        $this->s->withBorrowingPower('bF', 500);
        $line = $this->s->openLine('bF', 60, 6, 'monthly', null, 'S6: reverse REP');

        // Two REPs: 10 on row 0, 10 on row 1.
        $rep0 = $this->s->repayRow($line, 0, 10.0);
        $this->s->repayRow($line, 1, 10.0);

        $calc = new OutstandingCalculator();
        $this->assertEqualsWithDelta(40.0, $calc->recomputeForLine($line->refresh()), 0.0001);

        // Reverse rep0 through the full service so allocations are tidied up
        // and the linked schedule row reverts properly.
        $admin = UserExt::find($this->s->admin->id);
        $reverser = new ReverseService(
            new ReversalOutstandingRecomputer(new OutstandingCalculator())
        );
        $reverser->reverse($rep0->refresh(), $admin, 'S6: undo rep0');

        $rep0->refresh();
        $this->assertTrue((bool) $rep0->reversed, 'REP row must be flagged reversed=true');

        // Outstanding climbs back to 50 (only the second REP still counts).
        $this->assertEqualsWithDelta(50.0, $calc->recomputeForLine($line->refresh()), 0.0001,
            'reversed REP must be excluded from OutstandingCalculator');

        // Line stays active even though it had its first row paid (no auto
        // flip to paid_off on reversal — see ReverseService docblock §3).
        $this->assertSame(AccountCreditLineExt::STATUS_ACTIVE, $line->refresh()->status);

        // The row that rep0 had settled is now back to OPEN (scheduled or late).
        $row0 = $line->payments()->orderBy('due_date')->first();
        $this->assertNotSame(CreditLinePayment::STATUS_PAID, $row0->status,
            'row 0 must no longer be paid once its REP is reversed');
    }

    public function test_readjust_then_reverse_on_same_line(): void
    {
        $this->s->withBorrowingPower('bF', 500);
        $line = $this->s->openLine('bF', 60, 6, 'monthly', Carbon::today()->subMonths(2), 'S6: combined');

        $rep = $this->s->repayRow($line, 0, 10.0, Carbon::today()->subMonth());

        $admin = UserExt::find($this->s->admin->id);
        (new ReadjustService(new OutstandingCalculator(), new AmortizationScheduleBuilder()))
            ->readjust($line->refresh(), 12, null, $admin, 'S6 combined readjust');

        $line->refresh();
        $this->assertSame(12, (int) $line->term_months);

        // Reverse the pre-readjust REP.
        $reverser = new ReverseService(new ReversalOutstandingRecomputer(new OutstandingCalculator()));
        $reverser->reverse($rep->refresh(), $admin, 'S6 combined reverse');

        $calc = new OutstandingCalculator();
        $this->assertEqualsWithDelta(60.0, $calc->recomputeForLine($line->refresh()), 0.0001,
            'after readjust + reverse, outstanding equals principal again');

        $this->assertGreaterThanOrEqual(
            1,
            CreditLineAdjustment::where('account_credit_line_id', $line->id)->count(),
            'readjust still leaves its audit row intact after a later reversal'
        );

        $this->assertTrue(
            (bool) TransactionExt::find($rep->id)->reversed,
            'reverse path must persist reversed=true on the REP row'
        );
    }
}
