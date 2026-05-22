<?php

namespace Tests\Unit\Services\CreditLine\Draw;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Exceptions\OverBorrowException;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for DrawService (UC-01, UC-03, UC-04).
 */
class DrawServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // CASH asset must exist for fund setup (mirrors TradePortfolioControllerExtTest pattern).
        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        $this->service = new DrawService(
            new AmortizationScheduleBuilder(),
            new OutstandingCalculator()
        );
    }

    // -----------------------------------------------------------------
    // UC-01: Open first loan share (happy path)
    // -----------------------------------------------------------------

    public function test_open_creates_credit_line_with_correct_fields(): void
    {
        $account  = $this->factory->userAccount;
        $today    = Carbon::today();

        // Give the account 100 OWN shares.
        $this->seedOwnBalance($account, 100);

        $line = $this->service->open($account, 50.0, 12, 'monthly', 'Test line', $today);

        $this->assertInstanceOf(AccountCreditLine::class, $line);
        $this->assertEquals($account->id, $line->account_id);
        $this->assertEquals(50.0, $line->principal_shares);
        $this->assertEquals(50.0, $line->outstanding_shares);
        $this->assertEquals(12, $line->term_months);
        $this->assertEquals('monthly', $line->payment_frequency);
        $this->assertEquals(AccountCreditLineExt::STATUS_ACTIVE, $line->status);
        $this->assertEquals('Test line', $line->descr);
        $this->assertEquals($today->toDateString(), $line->origination_date->toDateString());
        $this->assertEquals($today->copy()->addMonths(12)->toDateString(), $line->maturity_date->toDateString());
    }

    public function test_open_creates_bor_transaction(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100);

        $line = $this->service->open($account, 30.0, 6, 'monthly');

        $borTran = TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_BORROW)
            ->first();

        $this->assertNotNull($borTran);
        $this->assertEquals(TransactionExt::STATUS_CLEARED, $borTran->status);
        $this->assertNull($borTran->credit_line_match_status);
        $this->assertEquals(30.0, (float) $borTran->shares);
    }

    public function test_open_12_month_monthly_generates_12_payments(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200);

        $line = $this->service->open($account, 100.0, 12, 'monthly');

        $payments = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->get();

        $this->assertCount(12, $payments);
        $this->assertEquals(CreditLinePayment::STATUS_SCHEDULED, $payments->first()->status);
    }

    public function test_schedule_sum_equals_outstanding_exactly(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200);

        // 100 shares / 12 months = 8.3333... — last payment absorbs remainder.
        $line = $this->service->open($account, 100.0, 12, 'monthly');

        $payments = CreditLinePayment::where('account_credit_line_id', $line->id)->get();
        $total    = $payments->sum('shares_due');

        $this->assertEquals(100.0, round($total, 4));
    }

    public function test_schedule_due_dates_are_monthly_from_origination(): void
    {
        $account    = $this->factory->userAccount;
        $today      = Carbon::today();
        $this->seedOwnBalance($account, 200);

        $line = $this->service->open($account, 60.0, 3, 'monthly', null, $today);

        $payments = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->get();

        $this->assertCount(3, $payments);
        $this->assertEquals($today->copy()->addMonths(1)->toDateString(), $payments[0]->due_date->toDateString());
        $this->assertEquals($today->copy()->addMonths(2)->toDateString(), $payments[1]->due_date->toDateString());
        $this->assertEquals($today->copy()->addMonths(3)->toDateString(), $payments[2]->due_date->toDateString());
    }

    public function test_open_creates_aggregate_bor_balance(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200);

        $this->service->open($account, 50.0, 12, 'monthly');

        $borBalance = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->whereDate('end_dt', '9999-12-31')
            ->first();

        $this->assertNotNull($borBalance);
        $this->assertEquals(50.0, (float) $borBalance->shares);
    }

    // -----------------------------------------------------------------
    // UC-03: Over-borrow (draw cap enforcement)
    // -----------------------------------------------------------------

    public function test_over_borrow_throws_exception(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 10.0);

        $this->expectException(OverBorrowException::class);

        $this->service->open($account, 15.0, 12, 'monthly');
    }

    public function test_over_borrow_exception_contains_correct_amounts(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 10.0);

        try {
            $this->service->open($account, 15.0, 12, 'monthly');
            $this->fail('Expected OverBorrowException');
        } catch (OverBorrowException $e) {
            $this->assertEquals(15.0, $e->getRequested());
            $this->assertEquals(10.0, $e->getAvailable());
        }
    }

    public function test_draw_cap_accounts_for_existing_active_lines(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 20.0);

        // First draw of 15 shares.
        $this->service->open($account, 15.0, 12, 'monthly');

        // Second draw: only 5 shares left → requesting 6 should fail.
        $this->expectException(OverBorrowException::class);

        $this->service->open($account, 6.0, 12, 'monthly');
    }

    public function test_nothing_committed_on_over_borrow(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 5.0);

        try {
            $this->service->open($account, 10.0, 12, 'monthly');
        } catch (OverBorrowException $e) {
            // expected
        }

        $lineCount = AccountCreditLine::where('account_id', $account->id)->count();
        $this->assertEquals(0, $lineCount);
    }

    // -----------------------------------------------------------------
    // Quarterly schedule
    // -----------------------------------------------------------------

    public function test_quarterly_schedule_generates_correct_payment_count(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200);

        // 12-month term, quarterly = 4 payments.
        $line = $this->service->open($account, 100.0, 12, 'quarterly');

        $payments = CreditLinePayment::where('account_credit_line_id', $line->id)->get();
        $this->assertCount(4, $payments);
        $this->assertEquals(100.0, round($payments->sum('shares_due'), 4));
    }

    public function test_annual_schedule_generates_correct_payment_count(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200);

        // 24-month term, annual = 2 payments.
        $line = $this->service->open($account, 100.0, 24, 'annual');

        $payments = CreditLinePayment::where('account_credit_line_id', $line->id)->get();
        $this->assertCount(2, $payments);
        $this->assertEquals(100.0, round($payments->sum('shares_due'), 4));
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
