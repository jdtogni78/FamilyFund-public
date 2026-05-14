<?php

namespace Tests\Unit\Services\CreditLine\Support;

use App\Models\AccountBalance;
use App\Models\AccountCreditLineBalance;
use App\Models\Asset;
use App\Models\FundExt;
use App\Models\TransactionExt;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Reporting\FundReceivableCalculator;
use App\Services\CreditLine\Reverse\ReversalOutstandingRecomputer;
use App\Services\CreditLine\Reverse\ReverseService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\CreditLineBalanceTracker;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for CreditLineBalanceTracker — verifies that the temporal
 * account_credit_line_balances table is correctly maintained by the
 * wave-1 services (Draw, Repay, Reverse) and that
 * FundReceivableCalculator can reconstruct the receivable as of any past date.
 */
class CreditLineBalanceTrackerTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $drawService;
    private RepayService $repayService;
    private ReverseService $reverseService;
    private CreditLineBalanceTracker $tracker;
    private FundReceivableCalculator $calc;

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

        $calculator      = new OutstandingCalculator();
        $scheduleBuilder = new AmortizationScheduleBuilder();
        $this->tracker   = new CreditLineBalanceTracker();
        $this->drawService    = new DrawService($scheduleBuilder, $calculator, $this->tracker);
        $this->repayService   = new RepayService($calculator, $this->tracker);
        $this->reverseService = new ReverseService(new ReversalOutstandingRecomputer(), $this->tracker);
        $this->calc = new FundReceivableCalculator();
    }

    public function test_draw_creates_initial_balance_row(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $originationDate = Carbon::parse('2026-01-01');
        $line = $this->drawService->open($account, 50.0, 12, 'monthly', null, $originationDate);

        $rows = AccountCreditLineBalance::where('account_credit_line_id', $line->id)->get();
        $this->assertCount(1, $rows);
        $this->assertEquals(50.0, $rows->first()->outstanding_shares);
        $this->assertEquals($originationDate->toDateString(), $rows->first()->start_dt->toDateString());
        $this->assertEquals('9999-12-31', $rows->first()->end_dt->toDateString());
    }

    public function test_repay_closes_prior_row_and_opens_new(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);

        $originationDate = Carbon::parse('2026-01-01');
        $line = $this->drawService->open($account, 100.0, 12, 'monthly', null, $originationDate);

        $repayDate = Carbon::parse('2026-02-15');
        $this->repayService->repay($line, 30.0, $repayDate);

        $rows = AccountCreditLineBalance::where('account_credit_line_id', $line->id)
            ->orderBy('start_dt')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $rows);
        // Prior row closed at the repay date.
        $this->assertEquals(100.0, $rows[0]->outstanding_shares);
        $this->assertEquals($repayDate->toDateString(), $rows[0]->end_dt->toDateString());
        // New row records the post-repay outstanding.
        $this->assertEquals(70.0, $rows[1]->outstanding_shares);
        $this->assertEquals($repayDate->toDateString(), $rows[1]->start_dt->toDateString());
        $this->assertEquals('9999-12-31', $rows[1]->end_dt->toDateString());
    }

    public function test_reverse_opens_new_row_with_recomputed_outstanding(): void
    {
        $account = $this->factory->userAccount;
        $admin = $this->factory->user; // any user — role enforced at controller layer
        $this->seedOwnBalance($account, 200.0);

        $line = $this->drawService->open($account, 100.0, 12, 'monthly', null, Carbon::parse('2026-01-01'));
        $rep = $this->repayService->repay($line, 30.0, Carbon::parse('2026-02-15'));

        // Reverse the REP — outstanding should return to 100.
        $this->reverseService->reverse($rep, $admin, 'test reversal');

        $rows = AccountCreditLineBalance::where('account_credit_line_id', $line->id)
            ->orderBy('start_dt')
            ->orderBy('id')
            ->get();

        // Original (100) → repay (70) → reverse (100). 3 rows.
        $this->assertCount(3, $rows);
        $this->assertEquals(100.0, $rows->last()->outstanding_shares);
        $this->assertEquals('9999-12-31', $rows->last()->end_dt->toDateString());
    }

    public function test_idempotent_when_outstanding_unchanged(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);
        $line = $this->drawService->open($account, 50.0, 12, 'monthly', null, Carbon::parse('2026-01-01'));

        // Manually call recordChange with the same outstanding — should be a no-op.
        $before = AccountCreditLineBalance::where('account_credit_line_id', $line->id)->count();
        $this->tracker->recordChange($line, 50.0, null, Carbon::parse('2026-02-01'));
        $after = AccountCreditLineBalance::where('account_credit_line_id', $line->id)->count();
        $this->assertEquals($before, $after);
    }

    public function test_receivable_calculator_returns_historical_value(): void
    {
        $fund = FundExt::find($this->factory->fund->id);
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);

        // Three temporal snapshots: open 100 on Jan 1, repay 30 on Feb 15, repay 20 on Mar 10.
        $line = $this->drawService->open($account, 100.0, 12, 'monthly', null, Carbon::parse('2026-01-01'));
        $this->repayService->repay($line, 30.0, Carbon::parse('2026-02-15'));
        $this->repayService->repay($line, 20.0, Carbon::parse('2026-03-10'));

        // Verify three as-of points produce the expected receivable.
        $this->assertEquals(100.0, $this->calc->receivableShares($fund, Carbon::parse('2026-01-15')));
        $this->assertEquals(70.0,  $this->calc->receivableShares($fund, Carbon::parse('2026-02-20')));
        $this->assertEquals(50.0,  $this->calc->receivableShares($fund, Carbon::parse('2026-03-15')));

        // Before origination — 0.
        $this->assertEquals(0.0, $this->calc->receivableShares($fund, Carbon::parse('2025-12-15')));
    }

    private function seedOwnBalance($account, float $shares): void
    {
        // Seed shares far back in the past so the test's historical draws
        // (origination_date in early 2026) pass the available-to-borrow check.
        $startDate = '2025-01-01';
        $tran = $this->factory->createTransaction(
            $shares * 10,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            $startDate
        );
        $tran->shares = $shares;
        $tran->save();

        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => $startDate,
            'end_dt'         => '9999-12-31',
        ]);
    }
}
