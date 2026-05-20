<?php

namespace Tests\Feature\Scenarios;

use App\Models\CreditLinePayment;
use App\Models\CreditLinePaymentAllocation;
use App\Models\TransactionExt;
use App\Models\UserExt;
use App\Services\CreditLine\Reverse\ReversalOutstandingRecomputer;
use App\Services\CreditLine\Reverse\ReverseService;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S11 — Backdated reversal of an old REP while later REPs already exist.
 *
 * The audit-safe reversal pipeline (ReverseService, see its docblock §3 / §5)
 * must:
 *   - flag the reversed REP with reversed = true (transaction row preserved)
 *   - drop the reversed REP's allocations from the ledger
 *   - re-open any rows whose only allocations came from the reversed REP
 *   - leave allocations from later REPs unaffected (rows they paid stay paid)
 *   - exclude the reversed REP from OutstandingCalculator
 *
 * Scenario:
 *   t-2mo  open 60-share line, 6 monthly rows of 10 each
 *   t-1mo  REP-A pays row 0 (auto-allocated to row 0 via cascade)
 *   t      REP-B pays row 1 (cascade past the paid row 0 lands on row 1)
 *   t+1d   reverse REP-A
 *
 * Expectations after reversal:
 *   - row 0 re-opens (status SCHEDULED or LATE, not PAID)
 *   - row 1 stays PAID (REP-B's allocation is untouched)
 *   - row 0 has zero allocations
 *   - row 1 has exactly one allocation, equal to shares_due
 *   - outstanding_shares climbs back up by REP-A's share count
 */
class S11BackdatedReversalCollisionTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    public function test_reversing_old_rep_re_opens_only_its_own_row(): void
    {
        $this->s->withBorrowingPower('bK', 500);
        $line = $this->s->openLine('bK', 60, 6, 'monthly', Carbon::today()->subMonths(2), 'S11 collision');

        // Two REPs on different rows. repayRow targets a specific row, so
        // we get a clean A→row0, B→row1 mapping.
        $repA = $this->s->repayRow($line, 0, 10.0, Carbon::today()->subMonth());
        $repB = $this->s->repayRow($line, 1, 10.0, Carbon::today());

        $rowsBefore = $this->s->rows($line);
        $this->assertSame(CreditLinePayment::STATUS_PAID, $rowsBefore->get(0)->status);
        $this->assertSame(CreditLinePayment::STATUS_PAID, $rowsBefore->get(1)->status);

        $calc = new OutstandingCalculator();
        $this->assertEqualsWithDelta(40.0, $calc->recomputeForLine($line->refresh()), 0.0001);

        // Reverse REP-A only.
        $admin = UserExt::find($this->s->admin->id);
        $reverser = new ReverseService(new ReversalOutstandingRecomputer(new OutstandingCalculator()));
        $reverser->reverse($repA->refresh(), $admin, 'S11: undo old REP-A');

        $repA->refresh();
        $this->assertTrue((bool) $repA->reversed, 'REP-A must be flagged reversed=true');

        $rowsAfter = $this->s->rows($line);
        $this->assertNotSame(
            CreditLinePayment::STATUS_PAID,
            $rowsAfter->get(0)->status,
            'row 0 must re-open once REP-A is reversed'
        );
        $this->assertSame(
            CreditLinePayment::STATUS_PAID,
            $rowsAfter->get(1)->status,
            'row 1 must remain PAID — REP-B\'s allocation is untouched by reversing REP-A'
        );

        // Allocation ledger
        $allocOnRow0 = CreditLinePaymentAllocation::where('credit_line_payment_id', $rowsAfter->get(0)->id)->count();
        $allocOnRow1 = CreditLinePaymentAllocation::where('credit_line_payment_id', $rowsAfter->get(1)->id)->count();
        $this->assertSame(0, $allocOnRow0, 'row 0\'s only allocation came from REP-A and must be gone');
        $this->assertSame(1, $allocOnRow1, 'row 1\'s allocation from REP-B must persist');

        // Outstanding climbs back to 50 (REP-A out, REP-B in).
        $this->assertEqualsWithDelta(
            50.0,
            $calc->recomputeForLine($line->refresh()),
            0.0001,
            'after reversing REP-A, only REP-B counts against outstanding'
        );
    }

    public function test_reversing_old_rep_leaves_subsequent_rep_transaction_record_intact(): void
    {
        $this->s->withBorrowingPower('bK', 500);
        $line = $this->s->openLine('bK', 30, 3, 'monthly', Carbon::today()->subMonth(), 'S11 record');

        $repA = $this->s->repayRow($line, 0, 10.0, Carbon::today()->subDays(20));
        $repB = $this->s->repayRow($line, 1, 10.0, Carbon::today()->subDays(5));

        $admin = UserExt::find($this->s->admin->id);
        $reverser = new ReverseService(new ReversalOutstandingRecomputer(new OutstandingCalculator()));
        $reverser->reverse($repA->refresh(), $admin, 'S11: record check');

        // REP-B's transaction row stays exactly as it was — reversed=false,
        // FK to the line preserved, shares unchanged.
        $repB = TransactionExt::findOrFail($repB->id);
        $this->assertFalse((bool) $repB->reversed, 'REP-B must NOT be flagged reversed');
        $this->assertSame((int) $line->id, (int) $repB->account_credit_line_id);
        $this->assertEqualsWithDelta(10.0, (float) $repB->shares, 0.0001);
    }
}
