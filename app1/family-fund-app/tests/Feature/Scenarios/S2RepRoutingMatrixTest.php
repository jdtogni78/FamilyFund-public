<?php

namespace Tests\Feature\Scenarios;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\CreditLinePayment;
use App\Models\CreditLinePaymentAllocation;
use App\Models\TransactionExt;
use App\Services\CreditLine\Matching\Contracts\ScheduleAdvancer;
use App\Services\CreditLine\Matching\MatchResolutionService;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S2 — REP routing matrix.
 *
 * Walks every CreditLineMatcher branch (UC-25 .. UC-30) plus the downstream
 * filters used by OutstandingCalculator:
 *
 *   - ambiguous      : 2 active lines, equal-shares scheduled rows
 *   - manual         : ambiguous REP resolved through MatchResolutionService
 *   - auto_matched   : payoff-by-outstanding (priority-3) hits exactly one line
 *   - unmatched      : account has zero active lines at the time the REP lands
 *   - reversed=true  : excluded from OutstandingCalculator regardless of FK
 *
 * The matcher must be exercised on a REP saved with account_credit_line_id =
 * NULL (so we can't go through RepayService, which pre-sets the FK and
 * bypasses the matcher).
 *
 * The TransactionObserver — registered on TransactionExt in AppServiceProvider —
 * fires on every TransactionExt save and routes the row through
 * CreditLineClassifier, so a plain `$tran->save()` with a null FK is enough
 * to drive the matcher in tests.
 */
class S2RepRoutingMatrixTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    public function test_ambiguous_then_manual_resolution_assigns_line_and_advances_schedule(): void
    {
        $this->s->withBorrowingPower('bB', 1000);
        // Identical lines: both have first-row shares_due = 10.0 → REP of 10 matches both.
        $l1 = $this->s->openLine('bB', 60, 6, 'monthly', null, 'S2: L1');
        $l2 = $this->s->openLine('bB', 60, 6, 'monthly', null, 'S2: L2');

        $rep = $this->makeUnassignedRep($this->s->account('bB'), 10.0);
        $rep->refresh();

        // ── ambiguous: REP matched both lines' first row
        $this->assertSame(TransactionExt::MATCH_STATUS_AMBIGUOUS, $rep->credit_line_match_status);
        $this->assertNull($rep->account_credit_line_id);
        $this->assertEqualsWithDelta(60.0, (float) $l1->refresh()->outstanding_shares, 0.0001);
        $this->assertEqualsWithDelta(60.0, (float) $l2->refresh()->outstanding_shares, 0.0001);

        // ── manual resolution → assigns L1, advances schedule, derives 'paid' on row 0
        $resolver = app(MatchResolutionService::class);
        $resolver->resolve($rep, $l1, $this->s->admin);

        $rep->refresh();
        $this->assertSame(TransactionExt::MATCH_STATUS_MANUAL, $rep->credit_line_match_status);
        $this->assertSame((int) $l1->id, (int) $rep->account_credit_line_id);

        // L1 outstanding (live recomputation) should be 50.
        $calc = new OutstandingCalculator();
        $this->assertEqualsWithDelta(50.0, $calc->recomputeForLine($l1->refresh()), 0.0001);

        // L1's first row should now be paid via the allocation ledger.
        $firstRow = CreditLinePayment::where('account_credit_line_id', $l1->id)
            ->orderBy('due_date')->firstOrFail();
        $this->assertSame(CreditLinePayment::STATUS_PAID, $firstRow->status);
        $this->assertEqualsWithDelta(
            10.0,
            (float) CreditLinePaymentAllocation::where('credit_line_payment_id', $firstRow->id)->sum('shares'),
            0.0001
        );

        // L2 untouched.
        $this->assertEqualsWithDelta(60.0, $calc->recomputeForLine($l2->refresh()), 0.0001);
    }

    public function test_outstanding_payoff_priority_3_auto_matches_only_one_line(): void
    {
        $this->s->withBorrowingPower('bB', 1000);
        // Two lines, identical principal. Repay L1's row 0 directly so its
        // outstanding diverges to 50 while L2 stays at 60.
        $l1 = $this->s->openLine('bB', 60, 6, 'monthly', null, 'S2 prio3 L1');
        $l2 = $this->s->openLine('bB', 60, 6, 'monthly', null, 'S2 prio3 L2');
        $this->s->repayRow($l1, 0, 10.0);

        $l1->refresh();
        $this->assertEqualsWithDelta(50.0, (float) $l1->outstanding_shares, 0.0001);

        // A REP whose shares equal exactly L1.outstanding_shares (50) — and
        // does NOT match any scheduled row on L2 (rows are 10) — should be
        // auto-matched to L1 via priority-3 (outstanding match).
        $rep = $this->makeUnassignedRep($this->s->account('bB'), 50.0);
        $rep->refresh();

        $this->assertSame(TransactionExt::MATCH_STATUS_AUTO_MATCHED, $rep->credit_line_match_status);
        $this->assertSame((int) $l1->id, (int) $rep->account_credit_line_id);
    }

    public function test_unmatched_rep_on_account_with_no_active_lines(): void
    {
        // Beneficiary has OWN balance but never opens a line; the matcher
        // produces UNMATCHED and the REP carries a NULL FK.
        $this->s->withBorrowingPower('bC', 100);
        $rep = $this->makeUnassignedRep($this->s->account('bC'), 5.0);
        $rep->refresh();

        $this->assertSame(TransactionExt::MATCH_STATUS_UNMATCHED, $rep->credit_line_match_status);
        $this->assertNull($rep->account_credit_line_id);
    }

    public function test_outstanding_calculator_ignores_ambiguous_unmatched_and_reversed_reps(): void
    {
        $this->s->withBorrowingPower('bB', 1000);
        $l1 = $this->s->openLine('bB', 60, 6, 'monthly', null, 'S2 filter L1');
        $l2 = $this->s->openLine('bB', 60, 6, 'monthly', null, 'S2 filter L2');

        // Ambiguous: 10-share REP matches both lines' first row.
        $ambiguous = $this->makeUnassignedRep($this->s->account('bB'), 10.0);
        $this->assertSame(TransactionExt::MATCH_STATUS_AMBIGUOUS, $ambiguous->refresh()->credit_line_match_status);

        // Reversed: a proper repayment (auto_matched) then flagged reversed=true.
        $this->s->repayRow($l1, 0, 10.0);                              // L1 outstanding → 50
        $reversed = $this->s->repay($l2, 10);                          // L2 outstanding → 50
        $this->s->reverseRep($reversed);                               // flag-flip; calc filter excludes it

        $calc = new OutstandingCalculator();
        $this->assertEqualsWithDelta(
            50.0,
            $calc->recomputeForLine($l1->refresh()),
            0.0001,
            'L1: BOR 60 − REP 10 (auto-matched) = 50; ambiguous REP must not count'
        );
        $this->assertEqualsWithDelta(
            60.0,
            $calc->recomputeForLine($l2->refresh()),
            0.0001,
            'L2: BOR 60 − reversed REP must equal full principal'
        );
    }

    /**
     * Build a REP transaction with account_credit_line_id = NULL; the
     * TransactionObserver fires on save and routes the row through the
     * matcher.
     */
    private function makeUnassignedRep(\App\Models\AccountExt $account, float $shares): TransactionExt
    {
        $tran = $this->s->df->createTransaction(
            $shares * 10,
            $account,
            TransactionExt::TYPE_REPAY,
            TransactionExt::STATUS_CLEARED,
            null,
            Carbon::today()->toDateString()
        );
        $tran->shares = $shares;
        $tran->account_credit_line_id = null;
        $tran->save();

        return $tran;
    }
}
