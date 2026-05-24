<?php

namespace Tests\Unit\Services\CreditLine\Repay;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for RepayService (UC-05, UC-06, UC-07, UC-13).
 */
class RepayServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $drawService;
    private RepayService $repayService;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        $calculator       = new OutstandingCalculator();
        $scheduleBuilder  = new AmortizationScheduleBuilder();
        $this->drawService  = new DrawService($scheduleBuilder, $calculator);
        $this->repayService = new RepayService($calculator);
    }

    // -----------------------------------------------------------------
    // UC-05: Scheduled on-time payment (full payment of first due row)
    // -----------------------------------------------------------------

    public function test_repay_full_first_row_marks_it_paid(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        // 3 payments of ~33.3333 each.
        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        $firstPayment = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->first();

        $this->repayService->repay($line, $firstPayment->shares_due);

        $firstPayment->refresh();
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $firstPayment->status);
        $this->assertNotNull($firstPayment->paid_transaction_id);
    }

    public function test_repay_reduces_outstanding_shares(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        $firstPayment = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->first();

        $this->repayService->repay($line, $firstPayment->shares_due);

        $line->refresh();
        $expectedOutstanding = round(100.0 - $firstPayment->shares_due, 4);
        $this->assertEquals($expectedOutstanding, round($line->outstanding_shares, 4));
    }

    public function test_repay_creates_rep_transaction(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        $repTran = $this->repayService->repay($line, 33.0);

        $this->assertInstanceOf(TransactionExt::class, $repTran);
        $this->assertEquals(TransactionExt::TYPE_REPAY, $repTran->type);
        $this->assertEquals(TransactionExt::STATUS_CLEARED, $repTran->status);
        $this->assertNull($repTran->credit_line_match_status);
        $this->assertEquals($line->id, $repTran->account_credit_line_id);
    }

    // -----------------------------------------------------------------
    // UC-06: Partial payment
    // -----------------------------------------------------------------

    public function test_partial_payment_marks_row_partial(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 90.0, 3, 'monthly');

        $firstPayment = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->first();

        // Pay less than shares_due.
        $partial = round($firstPayment->shares_due / 2, 4);
        $this->repayService->repay($line, $partial);

        $firstPayment->refresh();
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $firstPayment->status);
    }

    public function test_partial_payment_reduces_outstanding(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 90.0, 3, 'monthly');

        $this->repayService->repay($line, 10.0);

        $line->refresh();
        $this->assertEquals(80.0, round($line->outstanding_shares, 4));
    }

    // -----------------------------------------------------------------
    // UC-07: Extra / early payoff
    // -----------------------------------------------------------------

    public function test_extra_payment_cascades_to_next_rows(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);

        // 3 payments of 33.3333, 33.3333, 33.3334.
        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        // Pay more than the first row — covers first and second, leaves a partial on third.
        $this->repayService->repay($line, 70.0);

        $payments = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->get();

        $this->assertEquals(CreditLinePayment::STATUS_PAID, $payments[0]->status);
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $payments[1]->status);
        // Third row: 70 − 33.3333 − 33.3333 = 3.3334 applied → partial (not enough to cover 33.3334).
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $payments[2]->status);
    }

    // -----------------------------------------------------------------
    // UC-13: Payoff completion
    // -----------------------------------------------------------------

    public function test_full_repayment_sets_line_status_paid_off(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 60.0, 3, 'monthly');

        // Repay everything at once.
        $this->repayService->repay($line, 60.0);

        $line->refresh();
        $this->assertEquals(AccountCreditLineExt::STATUS_PAID_OFF, $line->status);
        $this->assertEquals(0.0, (float) $line->outstanding_shares);
    }

    public function test_full_repayment_marks_all_schedule_rows_paid(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 60.0, 3, 'monthly');

        $this->repayService->repay($line, 60.0);

        $unpaid = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->whereNotIn('status', [CreditLinePayment::STATUS_PAID])
            ->count();

        $this->assertEquals(0, $unpaid);
    }

    public function test_full_repayment_updates_aggregate_bor_balance_to_zero(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 3, 'monthly');

        // One open BOR balance row exists now.
        $borBefore = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->whereDate('end_dt', '9999-12-31')
            ->first();
        $this->assertNotNull($borBefore);

        $this->repayService->repay($line, 50.0);

        // After full repay, the open BOR row should be closed.
        $borAfter = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->whereDate('end_dt', '9999-12-31')
            ->first();
        $this->assertNull($borAfter);
    }

    // -----------------------------------------------------------------
    // Negative tests: guards added to RepayService
    // -----------------------------------------------------------------

    public function test_repay_with_negative_shares_throws(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 3, 'monthly');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/positive/i');

        $this->repayService->repay($line, -5.0);
    }

    public function test_repay_with_zero_shares_throws(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 3, 'monthly');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/positive/i');

        $this->repayService->repay($line, 0.0);
    }

    public function test_repay_against_cancelled_line_throws(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 0.0001, 3, 'monthly');
        // Cancel without any draws (outstanding = 0, so cancel is allowed).
        $line->outstanding_shares = 0;
        $line->save();
        $line->status = AccountCreditLineExt::STATUS_CANCELLED;
        $line->save();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cancelled/i');

        $this->repayService->repay($line, 10.0);
    }

    public function test_repay_against_paid_off_line_throws(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        // Open a line, repay it fully so it becomes paid_off.
        $line = $this->drawService->open($account, 30.0, 3, 'monthly');
        $this->repayService->repay($line, 30.0);

        $line->refresh();
        $this->assertEquals(AccountCreditLineExt::STATUS_PAID_OFF, $line->status);

        // Now trying to repay a paid_off line should throw.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/active/i');

        $this->repayService->repay($line, 5.0);
    }

    public function test_repay_overpayment_is_rejected(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 10.0, 1, 'monthly');

        // Attempt to repay 25 against a line that only has 10 outstanding.
        // Rejected: REP > outstanding would leave a BOR/REP mismatch (the
        // excess "repaid" shares would disappear from the borrow ledger
        // without returning to OWN). See QA-2026-05-20 #1.
        try {
            $this->repayService->repay($line, 25.0);
            $this->fail('Expected InvalidArgumentException for overpayment.');
        } catch (\InvalidArgumentException $e) {
            $this->assertMatchesRegularExpression('/outstanding/i', $e->getMessage());
        }

        // Line state is unchanged.
        $line->refresh();
        $this->assertEquals(AccountCreditLineExt::STATUS_ACTIVE, $line->status);
        $this->assertEquals(10.0, (float) $line->outstanding_shares);

        // No REP transaction was created.
        $repCount = TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_REPAY)
            ->count();
        $this->assertEquals(0, $repCount);
    }

    // -----------------------------------------------------------------
    // Regression: cascading overflow must not orphan an earlier partial
    // -----------------------------------------------------------------

    /**
     * Reproduces the credit-line-2 incident: an "expected" payment, then a
     * short payment that marks a row partial, then a larger payment whose
     * overflow cascades back onto that partial row.
     *
     * With the allocation ledger the earlier partial payment is never
     * orphaned: its allocation on row 2 stays put and the cascading overflow
     * is *added* alongside it (co-funding the row), rather than overwriting
     * the link as the old single-paid_transaction_id model did.
     */
    public function test_cascading_overflow_does_not_orphan_earlier_partial_payment(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 300.0);

        // 3 rows of ~33.3333 each.
        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        $rows = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->get();
        [$r1, $r2, $r3] = [$rows[0], $rows[1], $rows[2]];

        // 1) Expected payment fully settles row 1.
        $this->repayService->repayRow($r1, (float) $r1->shares_due);

        // 2) Short payment on row 2 → partial.
        $partialTran = $this->repayService->repayRow($r2, 20.0);

        // 3) Larger payment on row 3: covers row 3 (~33.33) and overflows;
        //    the overflow cascades back toward the oldest open row (row 2).
        //    Pay exactly the line's remaining outstanding so the cascade
        //    clears row 2 without overpaying the line total (which is now
        //    rejected; see test_repay_overpayment_is_rejected).
        $line->refresh();
        $remaining = (float) $line->outstanding_shares;
        $overflowTran = $this->repayService->repayRow($r3, $remaining);

        $r2->refresh();
        $r3->refresh();
        $partialTran->refresh();

        // The earlier short payment is NOT orphaned: it still has its
        // allocation on row 2, and the overflow is recorded alongside it.
        $r2Txs = \App\Models\CreditLinePaymentAllocation::where('credit_line_payment_id', $r2->id)
            ->pluck('transaction_id')
            ->all();
        $this->assertContains($partialTran->id, $r2Txs, 'Earlier partial payment was orphaned from row 2.');
        $this->assertContains($overflowTran->id, $r2Txs, 'Overflow was not recorded on row 2.');

        // Row 2 is now fully covered by the two payments combined (20 + 13.33).
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $r2->status);
        $this->assertFalse((bool) $partialTran->reversed);

        // Row 3 is paid by its own payment.
        $this->assertNotNull($r3->paid_transaction_id);
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $r3->status);
    }

    // -----------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------

    private function seedOwnBalance($account, float $shares): void
    {
        $tran = $this->factory->createTransaction(
            $shares * 10,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            Carbon::today()->toDateString()
        );
        $tran->shares = $shares;
        $tran->save();

        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => Carbon::today()->toDateString(),
            'end_dt'         => '9999-12-31',
        ]);
    }
}
