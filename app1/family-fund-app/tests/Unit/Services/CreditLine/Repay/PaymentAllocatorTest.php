<?php

namespace Tests\Unit\Services\CreditLine\Repay;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\CreditLinePaymentAllocation;
use App\Models\TransactionExt;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\PaymentAllocator;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for PaymentAllocator (allocation ledger).
 *
 * The ledger is the unit of truth: one tx -> many rows, one row -> many txs.
 * outstanding stays transaction-based and must be unaffected by allocation.
 */
class PaymentAllocatorTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $drawService;
    private PaymentAllocator $allocator;
    private OutstandingCalculator $calculator;

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
        $this->drawService = new DrawService(new AmortizationScheduleBuilder(), $this->calculator);
        $this->allocator   = new PaymentAllocator();
    }

    // -----------------------------------------------------------------
    // Helpers
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
            'descr'                    => 'test rep',
        ]);
    }

    /** @return CreditLinePayment[] ordered by due date */
    private function rows(AccountCreditLine $line): array
    {
        return CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->get()
            ->all();
    }

    // -----------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------

    public function test_one_payment_splits_across_many_rows(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);
        $line = $this->drawService->open($account, 100.0, 3, 'monthly'); // ~33.3333 x3

        [$r1, $r2, $r3] = $this->rows($line);

        // 70 covers r1 + r2 fully and part of r3.
        $tran = $this->rep($line, 70.0, '2026-02-01');
        $this->allocator->allocate($tran, $line);

        $r1->refresh();
        $r2->refresh();
        $r3->refresh();

        $this->assertEquals(CreditLinePayment::STATUS_PAID, $r1->status);
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $r2->status);
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $r3->status);

        // Whole 70 is allocated (nothing beyond schedule needed here).
        $this->assertEqualsWithDelta(
            70.0,
            (float) CreditLinePaymentAllocation::where('transaction_id', $tran->id)->sum('shares'),
            1e-4
        );
    }

    public function test_two_payments_one_row_no_orphan(): void
    {
        // The credit-line-2 essence: short payment then a later one finish a row.
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);
        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        [$r1, $r2] = $this->rows($line);

        $t1 = $this->rep($line, 20.0, '2026-02-10');
        $this->allocator->allocate($t1, $line, $r1);   // partial on r1

        $r1->refresh();
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $r1->status);

        $t2 = $this->rep($line, 40.0, '2026-02-20');
        $this->allocator->allocate($t2, $line, $r1);   // finishes r1 (~13.33), spills to r2

        $r1->refresh();
        $r2->refresh();

        // r1 fully covered by TWO transactions — neither orphaned.
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $r1->status);
        $allocs = CreditLinePaymentAllocation::where('credit_line_payment_id', $r1->id)
            ->pluck('transaction_id')->all();
        $this->assertContains($t1->id, $allocs);
        $this->assertContains($t2->id, $allocs);

        // Overflow from t2 reached r2.
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $r2->status);
    }

    public function test_unmatched_payment_allocates_oldest_first(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);
        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        [$r1, $r2, $r3] = $this->rows($line);

        // No target row (a payment that matched no installment).
        $tran = $this->rep($line, 33.3333, '2026-02-01');
        $this->allocator->allocate($tran, $line);

        $r1->refresh();
        $r2->refresh();

        $this->assertEquals(CreditLinePayment::STATUS_PAID, $r1->status);
        $this->assertEquals(CreditLinePayment::STATUS_SCHEDULED, $r2->status);
    }

    public function test_overpayment_beyond_schedule_leaves_remainder_unallocated(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);
        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        $tran = $this->rep($line, 250.0, '2026-02-01'); // far more than ~100 scheduled
        $this->allocator->allocate($tran, $line);

        $totalDue = round((float) CreditLinePayment::where('account_credit_line_id', $line->id)
            ->whereNotIn('status', [CreditLinePayment::STATUS_CANCELLED])
            ->sum('shares_due'), 4);
        $allocated = round((float) CreditLinePaymentAllocation::where('transaction_id', $tran->id)
            ->sum('shares'), 4);

        // Allocations capped at schedule; remainder is principal prepayment.
        $this->assertEqualsWithDelta($totalDue, $allocated, 1e-4);
        $this->assertLessThan(250.0, $allocated);

        foreach ($this->rows($line) as $row) {
            $this->assertEquals(CreditLinePayment::STATUS_PAID, $row->refresh()->status);
        }
    }

    public function test_manual_split_records_exact_amounts(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);
        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        [$r1, $r2] = $this->rows($line);

        $tran = $this->rep($line, 40.0, '2026-02-01');
        $this->allocator->allocate($tran, $line, null, [
            $r1->id => 25.0,
            $r2->id => 15.0,
        ]);

        $this->assertEqualsWithDelta(25.0, $this->allocator->sharesAllocatedOnRow($r1->refresh()), 1e-4);
        $this->assertEqualsWithDelta(15.0, $this->allocator->sharesAllocatedOnRow($r2->refresh()), 1e-4);
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $r1->status);
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $r2->status);
    }

    public function test_deallocate_removes_tx_and_rederives(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);
        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        [$r1] = $this->rows($line);

        $tran = $this->rep($line, 33.3333, '2026-02-01');
        $this->allocator->allocate($tran, $line, $r1);
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $r1->refresh()->status);

        $this->allocator->deallocate($tran);

        $this->assertEquals(
            0,
            CreditLinePaymentAllocation::where('transaction_id', $tran->id)->count()
        );
        $r1->refresh();
        $this->assertEquals(CreditLinePayment::STATUS_SCHEDULED, $r1->status);
        $this->assertNull($r1->paid_transaction_id);
    }

    public function test_outstanding_is_unaffected_by_allocation(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);
        $line = $this->drawService->open($account, 100.0, 3, 'monthly');

        $tran = $this->rep($line, 40.0, '2026-02-01');

        $before = $this->calculator->recomputeForLine($line->refresh());
        $this->allocator->allocate($tran, $line);
        $after = $this->calculator->recomputeForLine($line->refresh());

        // Allocation moves no money; outstanding is transaction-based.
        $this->assertEquals($before, $after);
    }
}
