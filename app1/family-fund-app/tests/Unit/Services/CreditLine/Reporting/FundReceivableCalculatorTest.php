<?php

namespace Tests\Unit\Services\CreditLine\Reporting;

use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\FundExt;
use App\Services\CreditLine\Reporting\FundReceivableCalculator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

class FundReceivableCalculatorTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
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
        $this->calc = new FundReceivableCalculator();
    }

    public function test_zero_when_no_active_lines(): void
    {
        $fund = FundExt::find($this->factory->fund->id);
        $this->assertSame(0.0, $this->calc->receivableShares($fund));
        $this->assertSame(0.0, $this->calc->receivableValue($fund));
    }

    public function test_sums_outstanding_shares_for_active_lines_only(): void
    {
        AccountCreditLine::create([
            'account_id'         => $this->factory->userAccount->id,
            'principal_shares'   => 50.0,
            'outstanding_shares' => 30.0,
            'term_months'        => 12,
            'origination_date'   => '2026-01-01',
            'maturity_date'      => '2027-01-01',
            'payment_frequency'  => 'monthly',
            'status'             => 'active',
        ]);
        AccountCreditLine::create([
            'account_id'         => $this->factory->userAccount->id,
            'principal_shares'   => 20.0,
            'outstanding_shares' => 20.0,
            'term_months'        => 6,
            'origination_date'   => '2026-02-01',
            'maturity_date'      => '2026-08-01',
            'payment_frequency'  => 'monthly',
            'status'             => 'paid_off', // excluded
        ]);
        AccountCreditLine::create([
            'account_id'         => $this->factory->userAccount->id,
            'principal_shares'   => 10.0,
            'outstanding_shares' => 5.0,
            'term_months'        => 12,
            'origination_date'   => '2026-03-01',
            'maturity_date'      => '2027-03-01',
            'payment_frequency'  => 'monthly',
            'status'             => 'cancelled', // excluded
        ]);

        $fund = FundExt::find($this->factory->fund->id);
        $this->assertSame(30.0, $this->calc->receivableShares($fund));
    }
}
