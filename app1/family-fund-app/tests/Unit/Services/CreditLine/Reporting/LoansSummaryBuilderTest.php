<?php

namespace Tests\Unit\Services\CreditLine\Reporting;

use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Services\CreditLine\Reporting\LoansSummaryBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

class LoansSummaryBuilderTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private LoansSummaryBuilder $builder;

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
        $this->builder = new LoansSummaryBuilder();
    }

    public function test_empty_account_returns_zero_aggregates(): void
    {
        $summary = $this->builder->forAccount($this->factory->userAccount);

        $this->assertSame(0.0, $summary['total_disbursed_shares']);
        $this->assertSame(0.0, $summary['total_repaid_shares']);
        $this->assertSame(0.0, $summary['net_outstanding_shares']);
        $this->assertSame(0, $summary['active_line_count']);
        $this->assertSame(0, $summary['behind_plan_count']);
        $this->assertNull($summary['next_due_date']);
    }

    public function test_aggregates_across_lines_and_counts_behind_plan(): void
    {
        $account = $this->factory->userAccount;

        $line1 = AccountCreditLine::create([
            'account_id'         => $account->id,
            'principal_shares'   => 100.0,
            'outstanding_shares' => 60.0,
            'term_months'        => 12,
            'origination_date'   => '2026-01-01',
            'maturity_date'      => '2027-01-01',
            'payment_frequency'  => 'monthly',
            'status'             => 'active',
        ]);
        $line2 = AccountCreditLine::create([
            'account_id'         => $account->id,
            'principal_shares'   => 50.0,
            'outstanding_shares' => 50.0,
            'term_months'        => 6,
            'origination_date'   => '2026-02-01',
            'maturity_date'      => '2026-08-01',
            'payment_frequency'  => 'monthly',
            'status'             => 'active',
        ]);

        // Past-due scheduled payment on line1 -> behind plan
        CreditLinePayment::create([
            'account_credit_line_id' => $line1->id,
            'due_date'               => Carbon::today()->subDays(10)->toDateString(),
            'shares_due'             => 10.0,
            'status'                 => CreditLinePayment::STATUS_SCHEDULED,
        ]);
        // Future payment on line2 -> next-due
        CreditLinePayment::create([
            'account_credit_line_id' => $line2->id,
            'due_date'               => Carbon::today()->addDays(5)->toDateString(),
            'shares_due'             => 8.0,
            'status'                 => CreditLinePayment::STATUS_SCHEDULED,
        ]);

        // Repay transactions: 40 paid on line1 (cleared, not reversed).
        Transaction::factory()->for($account, 'account')->create([
            'type'      => TransactionExt::TYPE_REPAY,
            'status'    => TransactionExt::STATUS_CLEARED,
            'value'     => 400,
            'shares'    => 40,
            'reversed'  => false,
            'timestamp' => now(),
            'account_credit_line_id' => $line1->id,
        ]);

        $summary = $this->builder->forAccount($account);

        $this->assertSame(150.0, $summary['total_disbursed_shares']);
        $this->assertSame(40.0, $summary['total_repaid_shares']);
        $this->assertSame(110.0, $summary['net_outstanding_shares']);
        $this->assertSame(2, $summary['active_line_count']);
        $this->assertSame(1, $summary['behind_plan_count']);
        $this->assertSame(Carbon::today()->addDays(5)->toDateString(), $summary['next_due_date']);
        $this->assertSame(8.0, $summary['next_due_shares']);
    }

    public function test_reversed_repayments_excluded_from_total_repaid(): void
    {
        $account = $this->factory->userAccount;
        // outstanding_shares is the snapshot OutstandingCalculator maintains
        // from non-reversed BOR/REP transactions: principal 100, one cleared
        // REP of 10 → outstanding 90. A reversed REP must not move the
        // snapshot, so total_repaid = 100 − 90 = 10.
        $line = AccountCreditLine::create([
            'account_id'         => $account->id,
            'principal_shares'   => 100.0,
            'outstanding_shares' => 90.0,
            'term_months'        => 12,
            'origination_date'   => '2026-01-01',
            'maturity_date'      => '2027-01-01',
            'payment_frequency'  => 'monthly',
            'status'             => 'active',
        ]);

        // One real repayment, one reversed.
        Transaction::factory()->for($account, 'account')->create([
            'type' => TransactionExt::TYPE_REPAY, 'status' => TransactionExt::STATUS_CLEARED,
            'value' => 100, 'shares' => 10, 'reversed' => false, 'timestamp' => now(),
            'account_credit_line_id' => $line->id,
        ]);
        Transaction::factory()->for($account, 'account')->create([
            'type' => TransactionExt::TYPE_REPAY, 'status' => TransactionExt::STATUS_CLEARED,
            'value' => 100, 'shares' => 10, 'reversed' => true, 'timestamp' => now(),
            'account_credit_line_id' => $line->id,
        ]);

        $summary = $this->builder->forAccount($account);
        $this->assertSame(10.0, $summary['total_repaid_shares']);
    }
}
