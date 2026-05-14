<?php

namespace Tests\Unit\Services\CreditLine\Reporting;

use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\FundExt;
use App\Services\CreditLine\Reporting\FundExposureBuilder;
use App\Services\CreditLine\Reporting\FundReceivableCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

class FundExposureBuilderTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private FundExposureBuilder $builder;

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
        $this->builder = new FundExposureBuilder(new FundReceivableCalculator());
    }

    public function test_zero_when_no_lines(): void
    {
        $fund = FundExt::find($this->factory->fund->id);
        $exposure = $this->builder->forFund($fund);
        $this->assertSame(0.0, $exposure['outstanding_shares']);
        $this->assertSame(0, $exposure['active_line_count']);
        $this->assertSame(0, $exposure['behind_plan_count']);
        $this->assertSame(0, $exposure['total_lines']);
    }

    public function test_counts_active_and_behind_plan(): void
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
        AccountCreditLine::create([
            'account_id'         => $account->id,
            'principal_shares'   => 40.0,
            'outstanding_shares' => 40.0,
            'term_months'        => 6,
            'origination_date'   => '2026-02-01',
            'maturity_date'      => '2026-08-01',
            'payment_frequency'  => 'monthly',
            'status'             => 'active',
        ]);
        // Past-due row on line1
        CreditLinePayment::create([
            'account_credit_line_id' => $line1->id,
            'due_date'               => Carbon::today()->subDays(7)->toDateString(),
            'shares_due'             => 10.0,
            'status'                 => CreditLinePayment::STATUS_SCHEDULED,
        ]);

        $fund = FundExt::find($this->factory->fund->id);
        $exposure = $this->builder->forFund($fund);

        $this->assertSame(100.0, $exposure['outstanding_shares']);
        $this->assertSame(2, $exposure['active_line_count']);
        $this->assertSame(1, $exposure['behind_plan_count']);
        $this->assertSame(2, $exposure['total_lines']);
    }
}
