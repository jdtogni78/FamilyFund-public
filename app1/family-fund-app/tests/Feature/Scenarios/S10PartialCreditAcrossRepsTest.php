<?php

namespace Tests\Feature\Scenarios;

use App\Models\CreditLinePayment;
use App\Models\CreditLinePaymentAllocation;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S10 — Partial credit across multiple REPs.
 *
 * Locks in the partial-credit accounting introduced/repaired by commit
 * 1e6bdc2b: a row that accumulates allocations from multiple REPs stays
 * PARTIAL until the cumulative allocation reaches shares_due, then flips
 * to PAID exactly once.
 *
 * Walks a single scheduled row through three 1/3-share REPs and asserts:
 *  - row.status transitions SCHEDULED → PARTIAL → PARTIAL → PAID
 *  - allocations ledger gains one row per REP
 *  - the sum of allocations across the row equals shares_due
 *  - outstanding_shares (live recomputation) decreases by the right amount
 *    after each REP
 *
 * The third REP is intentionally 0.0001 *more* than the remaining gap so
 * the row settles cleanly to PAID and the leftover goes to principal
 * prepayment (no rounding drift).
 */
class S10PartialCreditAcrossRepsTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    public function test_three_partial_reps_progress_row_through_partial_to_paid(): void
    {
        $this->s->withBorrowingPower('bJ', 500);
        // 60 shares / 6 months / monthly → first row shares_due = 10.0
        $line = $this->s->openLine('bJ', 60, 6, 'monthly', null, 'S10 partial credit');

        $row = $this->s->rows($line)->first();
        $this->assertEqualsWithDelta(10.0, (float) $row->shares_due, 0.0001);
        $this->assertSame(CreditLinePayment::STATUS_SCHEDULED, $row->status);

        $calc = new OutstandingCalculator();

        // REP #1: 3.333 shares → row PARTIAL, 1 allocation, outstanding 60 → 56.667
        $this->s->repay->repayRow($row, 3.3333);
        $row->refresh();
        $this->assertSame(CreditLinePayment::STATUS_PARTIAL, $row->status,
            'after the first partial repay, row.status must be PARTIAL');
        $this->assertSame(1, $this->allocationCount($row));
        $this->assertEqualsWithDelta(56.6667, $calc->recomputeForLine($line->refresh()), 0.0001);

        // REP #2: 3.333 more → row stays PARTIAL, 2 allocations, outstanding 53.333
        $this->s->repay->repayRow($row, 3.3333);
        $row->refresh();
        $this->assertSame(CreditLinePayment::STATUS_PARTIAL, $row->status,
            'a second partial repay must keep the row PARTIAL (not flip prematurely)');
        $this->assertSame(2, $this->allocationCount($row));
        $this->assertEqualsWithDelta(53.3334, $calc->recomputeForLine($line->refresh()), 0.0001);

        // REP #3: 3.3334 closes the gap → row PAID, 3 allocations, outstanding 50.0
        $this->s->repay->repayRow($row, 3.3334);
        $row->refresh();
        $this->assertSame(CreditLinePayment::STATUS_PAID, $row->status,
            'after the closing REP, row.status must flip to PAID');
        $this->assertSame(3, $this->allocationCount($row));
        $this->assertEqualsWithDelta(50.0, $calc->recomputeForLine($line->refresh()), 0.0001);

        // The allocations sum equals shares_due to within rounding tolerance.
        $allocatedSum = (float) CreditLinePaymentAllocation::where('credit_line_payment_id', $row->id)
            ->sum('shares');
        $this->assertEqualsWithDelta(10.0, $allocatedSum, 0.0001,
            'sum of three partial allocations must equal the row\'s shares_due');
    }

    public function test_subsequent_rows_remain_scheduled_while_first_row_is_partial(): void
    {
        $this->s->withBorrowingPower('bJ', 500);
        $line = $this->s->openLine('bJ', 60, 6, 'monthly', null, 'S10 partial isolation');

        $row = $this->s->rows($line)->first();
        $this->s->repay->repayRow($row, 4.0);

        $row->refresh();
        $this->assertSame(CreditLinePayment::STATUS_PARTIAL, $row->status);

        $siblingRows = $this->s->rows($line)
            ->where('id', '!=', $row->id)
            ->where('status', '!=', CreditLinePayment::STATUS_SCHEDULED)
            ->count();
        $this->assertSame(0, $siblingRows,
            'a partial REP on row 0 must not leak status changes to later rows');
    }

    private function allocationCount(CreditLinePayment $row): int
    {
        return CreditLinePaymentAllocation::where('credit_line_payment_id', $row->id)->count();
    }
}
