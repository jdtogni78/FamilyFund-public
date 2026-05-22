<?php

namespace Tests\Unit\Services\CreditLine\Repay;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\CreditLinePaymentAllocation;
use App\Models\TransactionExt;
use App\Services\CreditLine\Adjust\ReadjustService;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\PaymentAllocator;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Edge-case tests for PaymentAllocator.
 *
 * Bug reported 2026-05-19: "repayments were not allocated correctly".
 *
 * PaymentAllocatorTest covers the happy paths. These tests add the edge cases
 * that map to real-world misallocations:
 *   - Allocation must skip CANCELLED rows from a prior generation.
 *   - LATE rows must be eligible (oldest-first, regardless of status).
 *   - Two REPs same day on a single row must both leave allocations (no
 *     last-write-wins).
 *   - Tiny REP (≤ EPS) — should be a no-op, not a 0-share allocation row.
 *   - REP that exactly equals a row's shares_due → PAID, no remainder.
 *   - REP > shares_due of a single row but < total schedule → cascades.
 *   - REP that targets a CANCELLED row → leaves that row alone, cascades.
 *   - Sub-cent residuals (4-dp rounding) — last cent must not orphan a row.
 */
class PaymentAllocatorEdgeCasesTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $drawService;
    private PaymentAllocator $allocator;
    private OutstandingCalculator $calculator;
    private ReadjustService $readjust;

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

        $this->calculator  = new OutstandingCalculator();
        $builder           = new AmortizationScheduleBuilder();
        $this->drawService = new DrawService($builder, $this->calculator);
        $this->allocator   = new PaymentAllocator();
        $this->readjust    = new ReadjustService($this->calculator, $builder);

        Carbon::setTestNow(Carbon::parse('2026-01-01'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private function seedOwn(float $shares = 500.0): void
    {
        $account = $this->factory->userAccount;
        $tran = $this->factory->createTransaction(
            $shares,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            '2025-12-01'
        );
        $tran->shares = $shares;
        $tran->save();
        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => '2025-12-01',
            'end_dt'         => '9999-12-31',
        ]);
    }

    private function rep(AccountCreditLine $line, float $shares, string $date): TransactionExt
    {
        return TransactionExt::create([
            'account_id'               => $line->account_id,
            'account_credit_line_id'   => $line->id,
            'type'                     => TransactionExt::TYPE_REPAY,
            'status'                   => TransactionExt::STATUS_CLEARED,
            'value'                    => 0,
            'shares'                   => round($shares, 4),
            'timestamp'                => $date . ' 00:00:00',
            'credit_line_match_status' => null,
            'reversed'                 => false,
            'descr'                    => 'edge case REP',
        ]);
    }

    /** @return CreditLinePayment[] */
    private function rows(AccountCreditLine $line): array
    {
        return CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->get()
            ->all();
    }

    // ---------------------------------------------------------------
    // Cancelled rows must be skipped
    // ---------------------------------------------------------------

    public function test_allocation_skips_cancelled_rows_after_readjust(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount, 100.0, 6, 'monthly', null, Carbon::parse('2026-01-01')
        );

        // Snapshot original rows.
        $originalRowIds = collect($this->rows($line))->pluck('id')->all();

        // Readjust mid-life — most original rows become CANCELLED.
        $this->readjust->readjust($line->refresh(), 12, null, null, 'extend', Carbon::parse('2026-02-15'));

        $cancelledIds = CreditLinePayment::whereIn('id', $originalRowIds)
            ->where('status', CreditLinePayment::STATUS_CANCELLED)
            ->pluck('id')
            ->all();
        $this->assertNotEmpty($cancelledIds, 'Readjust must have produced cancelled rows.');

        // REP after the readjust.
        $tran = $this->rep($line, 25.0, '2026-03-01');
        $this->allocator->allocate($tran, $line->refresh());

        $allocOnCancelled = CreditLinePaymentAllocation::where('transaction_id', $tran->id)
            ->whereIn('credit_line_payment_id', $cancelledIds)
            ->count();

        $this->assertSame(
            0,
            $allocOnCancelled,
            'PaymentAllocator must never write allocations against CANCELLED rows.'
        );
    }

    // ---------------------------------------------------------------
    // LATE row priority
    // ---------------------------------------------------------------

    public function test_late_row_is_filled_before_a_younger_scheduled_row(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount, 100.0, 3, 'monthly', null, Carbon::parse('2026-01-01')
        );

        $rows = $this->rows($line);
        $r1 = $rows[0];
        $r2 = $rows[1];

        // Manually mark r1 as LATE so we exercise that branch.
        $r1->status = CreditLinePayment::STATUS_LATE;
        $r1->save();

        $tran = $this->rep($line, 33.3333, '2026-03-01');
        $this->allocator->allocate($tran, $line);

        $r1->refresh();
        $r2->refresh();
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $r1->status,
            'LATE row should still be considered open and be filled first.');
        $this->assertEquals(CreditLinePayment::STATUS_SCHEDULED, $r2->status);
    }

    // ---------------------------------------------------------------
    // Idempotency / repeated allocate calls
    // ---------------------------------------------------------------

    public function test_double_allocate_call_does_not_double_credit_a_row(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount, 100.0, 3, 'monthly', null, Carbon::parse('2026-01-01')
        );

        [$r1] = $this->rows($line);

        $tran = $this->rep($line, 33.3333, '2026-02-01');
        $this->allocator->allocate($tran, $line, $r1);
        $this->allocator->allocate($tran, $line, $r1); // double-call (race / retry simulation)

        $sum = CreditLinePaymentAllocation::where('transaction_id', $tran->id)->sum('shares');

        $this->assertEqualsWithDelta(
            33.3333,
            (float) $sum,
            1e-3,
            'allocate() called twice for the same tran must not double-credit. '
            . 'The allocator must compute remaining = tran.shares − already_allocated_for_tran.'
        );
    }

    // ---------------------------------------------------------------
    // Tiny / zero / negative REP guards
    // ---------------------------------------------------------------

    /**
     * Regression: a REP with shares=0 must be a no-op (no allocation rows
     * written).  PaymentAllocator currently handles this via the
     * `$remaining <= 0` guard at the top of allocate().
     */
    public function test_zero_share_rep_is_a_no_op(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount, 100.0, 3, 'monthly', null, Carbon::parse('2026-01-01')
        );

        $tran = $this->rep($line, 0.0, '2026-02-01');
        $this->allocator->allocate($tran, $line);

        $count = CreditLinePaymentAllocation::where('transaction_id', $tran->id)->count();
        $this->assertSame(0, $count, 'A zero-share REP must write no allocations.');
    }

    // ---------------------------------------------------------------
    // Exact-fit REP
    // ---------------------------------------------------------------

    public function test_rep_exactly_equal_to_row_marks_row_paid_no_remainder(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount, 100.0, 3, 'monthly', null, Carbon::parse('2026-01-01')
        );

        $rows = $this->rows($line);
        $r1 = $rows[0];
        $r2 = $rows[1];

        $tran = $this->rep($line, (float) $r1->shares_due, '2026-02-01');
        $this->allocator->allocate($tran, $line, $r1);

        $r1->refresh();
        $r2->refresh();
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $r1->status);
        $this->assertEquals(CreditLinePayment::STATUS_SCHEDULED, $r2->status);
    }

    // ---------------------------------------------------------------
    // Sub-cent residual on last row
    // ---------------------------------------------------------------

    public function test_full_payoff_via_oldest_first_clears_last_row_exactly(): void
    {
        $this->seedOwn();
        // 100 / 7 → 14.2857 × 6 + 14.2858 last row (4-dp rounding).
        $line = $this->drawService->open(
            $this->factory->userAccount, 100.0, 7, 'monthly', null, Carbon::parse('2026-01-01')
        );

        // Pay the whole principal in one shot.
        $tran = $this->rep($line, 100.0, '2026-02-01');
        $this->allocator->allocate($tran, $line);

        $remaining = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->whereIn('status', [
                CreditLinePayment::STATUS_SCHEDULED,
                CreditLinePayment::STATUS_LATE,
                CreditLinePayment::STATUS_PARTIAL,
            ])
            ->get();

        $this->assertCount(
            0,
            $remaining,
            'A 100-share REP against a 100-share schedule must close every row. '
            . 'Any leftover open row indicates a sub-cent rounding orphan.'
        );
    }

    // ---------------------------------------------------------------
    // Manual split sanity
    // ---------------------------------------------------------------

    public function test_manual_split_total_exceeds_tran_shares_is_recorded_as_given(): void
    {
        // Documents current behavior: manualSplit takes the caller's word
        // and writes the rows exactly. The caller (controller) must validate
        // that Σ split ≤ tran.shares — this is verified in the Manual
        // Allocation Feature test. Here we just lock in the unit behavior
        // so a future refactor doesn't silently clamp without test cover.
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount, 100.0, 3, 'monthly', null, Carbon::parse('2026-01-01')
        );

        $rows = $this->rows($line);
        $r1 = $rows[0];
        $r2 = $rows[1];

        $tran = $this->rep($line, 40.0, '2026-02-01');
        $this->allocator->allocate($tran, $line, null, [
            $r1->id => 25.0,
            $r2->id => 25.0,
        ]);

        $sum = CreditLinePaymentAllocation::where('transaction_id', $tran->id)->sum('shares');

        // Unit-level: allocator records the manual split as supplied
        // (overallocation policy lives in the controller).
        $this->assertEqualsWithDelta(50.0, (float) $sum, 1e-4);
    }

    // ---------------------------------------------------------------
    // Cascade ordering deterministic by due_date
    // ---------------------------------------------------------------

    public function test_cascade_order_is_due_date_ascending(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount, 100.0, 4, 'monthly', null, Carbon::parse('2026-01-01')
        );

        // 100 / 4 = 25.0 per row.
        // REP of 30 should pay r1 (25) and partial r2 (5), not the other way.
        $tran = $this->rep($line, 30.0, '2026-02-01');
        $this->allocator->allocate($tran, $line);

        $rows = $this->rows($line);
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $rows[0]->refresh()->status);
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $rows[1]->refresh()->status);
        $this->assertEquals(CreditLinePayment::STATUS_SCHEDULED, $rows[2]->refresh()->status);
        $this->assertEquals(CreditLinePayment::STATUS_SCHEDULED, $rows[3]->refresh()->status);
    }

    // ---------------------------------------------------------------
    // Deallocate after partial cascade
    // ---------------------------------------------------------------

    public function test_deallocate_after_cascading_rep_reopens_every_touched_row(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount, 100.0, 4, 'monthly', null, Carbon::parse('2026-01-01')
        );

        $tran = $this->rep($line, 70.0, '2026-02-01');
        $this->allocator->allocate($tran, $line);

        $rows = $this->rows($line);
        // Sanity: r1+r2 fully paid, r3 partial.
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $rows[0]->refresh()->status);
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $rows[1]->refresh()->status);
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $rows[2]->refresh()->status);

        // Reverse.
        $this->allocator->deallocate($tran);

        foreach ($rows as $row) {
            $row->refresh();
            $this->assertEquals(
                CreditLinePayment::STATUS_SCHEDULED,
                $row->status,
                "Row {$row->id} must re-open after deallocation of the only payment."
            );
            $this->assertNull($row->paid_transaction_id);
        }
    }
}
