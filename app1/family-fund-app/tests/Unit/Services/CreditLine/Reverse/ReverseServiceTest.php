<?php

namespace Tests\Unit\Services\CreditLine\Reverse;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Models\TransactionReversal;
use App\Models\UserExt;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Reverse\Exceptions\AlreadyReversedException;
use App\Services\CreditLine\Reverse\Exceptions\NotReversibleTypeException;
use App\Services\CreditLine\Reverse\ReversalOutstandingRecomputer;
use App\Services\CreditLine\Reverse\ReverseService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for ReverseService (UC-45).
 */
class ReverseServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $drawService;
    private RepayService $repayService;
    private ReverseService $reverseService;
    private UserExt $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // CASH asset required by DataFactory::createFund.
        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        $calculator          = new OutstandingCalculator();
        $scheduleBuilder     = new AmortizationScheduleBuilder();
        $recomputer          = new ReversalOutstandingRecomputer();

        $this->drawService    = new DrawService($scheduleBuilder, $calculator);
        $this->repayService   = new RepayService($calculator);
        $this->reverseService = new ReverseService($recomputer);

        // Use the test user as the "admin" for audit purposes.
        // Phase 2 will add real role enforcement at the controller layer.
        $this->adminUser = UserExt::find($this->factory->user->id);
    }

    // ------------------------------------------------------------------
    // UC-45: Reverse a REP that paid a schedule row
    // ------------------------------------------------------------------

    public function test_reverse_rep_reopens_paid_schedule_row(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 90.0, 3, 'monthly');

        $firstRow = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->first();

        $repTran = $this->repayService->repay($line, $firstRow->shares_due);

        $firstRow->refresh();
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $firstRow->status);

        // Now reverse the REP.
        $this->reverseService->reverse($repTran, $this->adminUser, 'Wrong line target');

        $firstRow->refresh();
        $this->assertContains(
            $firstRow->status,
            [CreditLinePayment::STATUS_SCHEDULED, CreditLinePayment::STATUS_LATE],
            'Row should be re-opened to scheduled or late after REP reversal'
        );
        $this->assertNull($firstRow->paid_transaction_id);
    }

    public function test_reverse_rep_reopens_partial_schedule_row(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 90.0, 3, 'monthly');

        $firstRow = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->first();

        // Partial payment — leaves row in partial state.
        $partial = round($firstRow->shares_due / 2, 4);
        $repTran = $this->repayService->repay($line, $partial);

        $firstRow->refresh();
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $firstRow->status);

        // Reverse the partial REP.
        $this->reverseService->reverse($repTran, $this->adminUser, 'Entered wrong amount');

        $firstRow->refresh();
        $this->assertContains(
            $firstRow->status,
            [CreditLinePayment::STATUS_SCHEDULED, CreditLinePayment::STATUS_LATE],
            'Partially-paid row should be re-opened after REP reversal'
        );
        $this->assertNull($firstRow->paid_transaction_id);
    }

    public function test_reverse_rep_restores_outstanding_shares(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 60.0, 3, 'monthly');

        $firstRow = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->first();

        $this->repayService->repay($line, $firstRow->shares_due);
        $line->refresh();
        $outstandingAfterRepay = (float) $line->outstanding_shares;

        $repTran = TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_REPAY)
            ->latest()
            ->first();

        $this->reverseService->reverse($repTran, $this->adminUser, 'Test reason');

        $line->refresh();
        $this->assertEquals(
            60.0,
            round($line->outstanding_shares, 4),
            'outstanding_shares should be restored to original principal after reversing the only REP'
        );
        $this->assertGreaterThan($outstandingAfterRepay, $line->outstanding_shares);
    }

    public function test_reverse_rep_writes_audit_row_with_all_fields(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 2, 'monthly');

        $repTran = $this->repayService->repay($line, 25.0);

        $reversal = $this->reverseService->reverse($repTran, $this->adminUser, 'Audit test reason');

        $this->assertInstanceOf(TransactionReversal::class, $reversal);
        $this->assertEquals($repTran->id, $reversal->transaction_id);
        $this->assertEquals($line->id, $reversal->original_target_credit_line_id);
        $this->assertNotNull($reversal->reversed_at);
        $this->assertEquals($this->adminUser->id, $reversal->reversed_by_user_id);
        $this->assertEquals('Audit test reason', $reversal->reason);
    }

    public function test_reverse_rep_fully_paid_off_reopens_line(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 30.0, 1, 'monthly');

        // Fully repay the line → status = paid_off.
        $repTran = $this->repayService->repay($line, 30.0);

        $line->refresh();
        $this->assertEquals(AccountCreditLineExt::STATUS_PAID_OFF, $line->status);

        // Reverse the REP that paid it off.
        $this->reverseService->reverse($repTran, $this->adminUser, 'Reversing full payoff');

        $line->refresh();
        $this->assertEquals(
            AccountCreditLineExt::STATUS_ACTIVE,
            $line->status,
            'Line should revert to active when the payment that triggered paid_off is reversed'
        );
        $this->assertGreaterThan(0, $line->outstanding_shares);
    }

    // ------------------------------------------------------------------
    // UC-45: Reverse a BOR
    // ------------------------------------------------------------------

    public function test_reverse_bor_decreases_outstanding(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 3, 'monthly');

        $borTran = TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_BORROW)
            ->first();

        $this->assertNotNull($borTran, 'DrawService should have created a BOR transaction');

        $this->reverseService->reverse($borTran, $this->adminUser, 'Draw was a mistake');

        $line->refresh();
        // BOR is now reversed; outstanding should drop to 0 (principal no longer counted).
        $this->assertEquals(0.0, round($line->outstanding_shares, 4));
    }

    public function test_reverse_bor_does_not_auto_set_paid_off(): void
    {
        // Design decision: reversing a BOR that brings outstanding to 0 does NOT
        // auto-set the line to paid_off. paid_off is a forward-state-only transition.
        // The line stays active — the admin must explicitly cancel or close it.
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 3, 'monthly');

        $borTran = TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_BORROW)
            ->first();

        $this->reverseService->reverse($borTran, $this->adminUser, 'Draw error');

        $line->refresh();
        $this->assertNotEquals(
            AccountCreditLineExt::STATUS_PAID_OFF,
            $line->status,
            'Reversing a BOR should NOT auto-set line status to paid_off'
        );
    }

    // ------------------------------------------------------------------
    // Error: reverse twice
    // ------------------------------------------------------------------

    public function test_reverse_twice_throws_already_reversed_exception(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 3, 'monthly');

        $repTran = $this->repayService->repay($line, 10.0);

        $this->reverseService->reverse($repTran, $this->adminUser, 'First reversal');

        $this->expectException(AlreadyReversedException::class);
        $this->reverseService->reverse($repTran, $this->adminUser, 'Second reversal attempt');
    }

    // ------------------------------------------------------------------
    // Error: non-reversible type (PUR, SAL)
    // ------------------------------------------------------------------

    public function test_reverse_purchase_throws_not_reversible_type_exception(): void
    {
        $account = $this->factory->userAccount;

        $purTran = TransactionExt::create([
            'account_id' => $account->id,
            'type'       => TransactionExt::TYPE_PURCHASE,
            'status'     => TransactionExt::STATUS_CLEARED,
            'value'      => 100,
            'shares'     => 10,
            'timestamp'  => Carbon::today()->toDateTimeString(),
            'reversed'   => false,
        ]);

        $this->expectException(NotReversibleTypeException::class);
        $this->reverseService->reverse($purTran, $this->adminUser, 'Should fail');
    }

    public function test_reverse_sale_throws_not_reversible_type_exception(): void
    {
        $account = $this->factory->userAccount;

        $salTran = TransactionExt::create([
            'account_id' => $account->id,
            'type'       => TransactionExt::TYPE_SALE,
            'status'     => TransactionExt::STATUS_CLEARED,
            'value'      => -100,
            'shares'     => -10,
            'timestamp'  => Carbon::today()->toDateTimeString(),
            'reversed'   => false,
        ]);

        $this->expectException(NotReversibleTypeException::class);
        $this->reverseService->reverse($salTran, $this->adminUser, 'Should fail');
    }

    // ------------------------------------------------------------------
    // Error: empty reason
    // ------------------------------------------------------------------

    public function test_reverse_with_empty_reason_throws_invalid_argument_exception(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line    = $this->drawService->open($account, 50.0, 3, 'monthly');
        $repTran = $this->repayService->repay($line, 10.0);

        $this->expectException(InvalidArgumentException::class);
        $this->reverseService->reverse($repTran, $this->adminUser, '');
    }

    public function test_reverse_with_whitespace_only_reason_throws_invalid_argument_exception(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line    = $this->drawService->open($account, 50.0, 3, 'monthly');
        $repTran = $this->repayService->repay($line, 10.0);

        $this->expectException(InvalidArgumentException::class);
        $this->reverseService->reverse($repTran, $this->adminUser, '   ');
    }

    // ------------------------------------------------------------------
    // Transaction row is flagged, not deleted
    // ------------------------------------------------------------------

    public function test_reversed_transaction_still_exists_in_db(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line    = $this->drawService->open($account, 50.0, 3, 'monthly');
        $repTran = $this->repayService->repay($line, 10.0);
        $tranId  = $repTran->id;

        $this->reverseService->reverse($repTran, $this->adminUser, 'Keep the row');

        $found = TransactionExt::find($tranId);
        $this->assertNotNull($found, 'Reversed transaction row must not be deleted');
        $this->assertTrue((bool) $found->reversed);
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

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
