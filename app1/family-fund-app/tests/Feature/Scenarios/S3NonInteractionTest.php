<?php

namespace Tests\Feature\Scenarios;

use App\Models\AccountBalance;
use App\Models\CreditLinePaymentAllocation;
use App\Models\TransactionExt;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S3 — Non-interaction: PUR / SAL / MAT do not touch credit-line state.
 *
 * Proves that the credit-line subsystem is well-isolated from the rest of the
 * transaction taxonomy:
 *
 *   - Matching contributions (MAT) created by an employer-match rule
 *   - Deposits (PUR) outside the borrowing flow
 *   - Withdrawals (SAL) outside the borrowing flow
 *
 * After firing all three on a beneficiary with an active credit line, the
 * line's outstanding_shares, the BOR aggregate row, the allocation ledger,
 * and the credit_line_match_status on the surrounding transactions must all
 * be unchanged.
 *
 * The gatekeeper is TransactionObserver::RELEVANT_TYPES (BOR/REP/PUR only);
 * MAT/SAL/INI never enter the detection pipeline. PUR enters but the matcher
 * is a no-op for non-REP types (see CreditLineMatcher::match guard 1).
 */
class S3NonInteractionTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    public function test_pur_sal_mat_do_not_touch_credit_line_state(): void
    {
        $this->s->withBorrowingPower('bC', 500);
        $this->s->attachMatchingRule('bC', dollarEnd: 10_000, matchPct: 50);

        $line = $this->s->openLine('bC', 80, 8, 'monthly', null, 'S3: active line');
        $this->s->repayRow($line, 0, 10.0);                            // outstanding → 70

        // Snapshot every credit-line-relevant piece of state.
        $account     = $this->s->account('bC');
        $calc        = new OutstandingCalculator();
        $outBefore   = $calc->recomputeForLine($line->refresh());
        $borBefore   = (float) AccountBalance::where('account_id', $account->id)
                                ->where('type', 'BOR')->where('end_dt', '9999-12-31')
                                ->orderByDesc('id')->value('shares');
        $allocBefore = CreditLinePaymentAllocation::whereIn(
            'credit_line_payment_id',
            $line->payments()->pluck('id')
        )->count();
        $relevantRepIds = TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_REPAY)->pluck('id')->all();

        // ── Fire MAT first via the matching-rule flow.
        $matched = $this->s->triggerEmployerMatch('bC', depositShares: 20, date: Carbon::today()->subDays(2));
        // ── Then a stand-alone deposit and withdrawal.
        $pur = $this->s->addPurchase('bC', 15, Carbon::today()->subDay());
        $sal = $this->s->addWithdrawal('bC', 5,  Carbon::today());

        // ── Credit-line outstanding unchanged.
        $this->assertEqualsWithDelta($outBefore, $calc->recomputeForLine($line->refresh()), 0.0001,
            'PUR/SAL/MAT must not alter recomputed outstanding shares');

        // ── BOR aggregate row unchanged.
        $borAfter = (float) AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')->where('end_dt', '9999-12-31')
            ->orderByDesc('id')->value('shares');
        $this->assertEqualsWithDelta($borBefore, $borAfter, 0.0001,
            'BOR aggregate row must be untouched by non-borrowing transactions');

        // ── Allocations unchanged.
        $allocAfter = CreditLinePaymentAllocation::whereIn(
            'credit_line_payment_id',
            $line->payments()->pluck('id')
        )->count();
        $this->assertSame($allocBefore, $allocAfter, 'allocation ledger must not gain rows');

        // ── No new REP appeared on the line.
        $this->assertEquals(
            $relevantRepIds,
            TransactionExt::where('account_credit_line_id', $line->id)
                ->where('type', TransactionExt::TYPE_REPAY)->pluck('id')->all(),
            'no new REP should be linked to the credit line'
        );

        // ── The MAT / PUR / SAL transactions themselves carry no credit-line match status.
        $this->assertNull($matched['match']->credit_line_match_status, 'MAT is filtered out by TransactionObserver');
        $this->assertNull($matched['match']->account_credit_line_id);
        $this->assertNull($pur->refresh()->credit_line_match_status, 'PUR is not a REP — matcher returns no-op');
        $this->assertNull($pur->account_credit_line_id);
        $this->assertNull($sal->refresh()->credit_line_match_status, 'SAL is filtered out by TransactionObserver');
        $this->assertNull($sal->account_credit_line_id);
    }

    public function test_net_shares_reflect_pur_and_sal_but_not_credit_line_outstanding_change(): void
    {
        $this->s->withBorrowingPower('bC', 200);
        $line = $this->s->openLine('bC', 80, 8, 'monthly', null, 'S3: net check');
        // OWN=200, BOR=80, net=120
        $this->assertEqualsWithDelta(120.0, $this->s->sharesAsOf('bC'), 0.0001);

        $this->s->addPurchase('bC', 50);                               // OWN→250
        $this->assertEqualsWithDelta(170.0, $this->s->sharesAsOf('bC'), 0.0001);

        $this->s->addWithdrawal('bC', 30);                             // OWN→220
        $this->assertEqualsWithDelta(140.0, $this->s->sharesAsOf('bC'), 0.0001);

        // Outstanding on the credit line is still 80 (no REP happened).
        $calc = new OutstandingCalculator();
        $this->assertEqualsWithDelta(80.0, $calc->recomputeForLine($line->refresh()), 0.0001);
    }
}
