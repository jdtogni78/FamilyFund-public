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
