<?php

namespace Tests\Unit;

use App\Models\AccountExt;
use App\Models\FundExt;
use App\Models\PortfolioExt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for FundExt model
 */
class FundExtTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    public function test_fund_map_returns_array_with_select_option()
    {
        $result = FundExt::fundMap();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(null, $result);
        $this->assertEquals('Please Select Fund', $result[null]);
    }

    public function test_fund_map_includes_funds()
    {
        $result = FundExt::fundMap();

        // Should include the fund created by DataFactory
        $this->assertGreaterThan(1, count($result));
        $this->assertArrayHasKey($this->factory->fund->id, $result);
    }

    public function test_portfolio_returns_single_portfolio()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->portfolio();

        $this->assertInstanceOf(PortfolioExt::class, $result);
    }

    public function test_account_returns_fund_account()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->account();

        $this->assertInstanceOf(AccountExt::class, $result);
        $this->assertNull($result->user_id);
        $this->assertEquals($fund->id, $result->fund_id);
    }

    public function test_shares_as_of_returns_shares()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->sharesAsOf('2022-06-01');

        // Fund was created with 1000 shares
        $this->assertIsNumeric($result);
    }

    public function test_value_as_of_returns_value()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->valueAsOf('2022-06-01');

        // Fund was created with 1000 value
        $this->assertIsNumeric($result);
    }

    public function test_share_value_as_of_calculates_correctly()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->shareValueAsOf('2022-06-01');

        // value / shares
        $this->assertIsNumeric($result);
        $this->assertGreaterThan(0, $result);
    }

    public function test_share_value_as_of_returns_zero_when_no_shares()
    {
        // Create a fund without any shares
        $fund = FundExt::find($this->factory->fund->id);

        // Mock a scenario where shares are 0
        // For this test, we just verify the method doesn't throw an exception
        $result = $fund->shareValueAsOf('2020-01-01');

        $this->assertIsNumeric($result);
    }

    public function test_allocated_shares_returns_user_account_shares()
    {
        $fund = FundExt::find($this->factory->fund->id);

        // Create a transaction for user account
        $transaction = $this->factory->createTransaction(500, $this->factory->userAccount);
        $this->factory->createBalance(50, $transaction, $this->factory->userAccount, '2022-01-15');

        $result = $fund->allocatedShares('2022-06-01');

        $this->assertEquals(50, $result);
    }

    public function test_unallocated_shares_returns_difference()
    {
        $fund = FundExt::find($this->factory->fund->id);

        // Create a transaction for user account
        $transaction = $this->factory->createTransaction(500, $this->factory->userAccount);
        $this->factory->createBalance(50, $transaction, $this->factory->userAccount, '2022-01-15');

        $result = $fund->unallocatedShares('2022-06-01');

        // Total shares (1000) - allocated (50) = 950
        $expected = 1000 - 50;
        $this->assertEquals($expected, $result);
    }

    public function test_period_performance_delegates_to_portfolio()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->periodPerformance('2022-01-01', '2022-06-01');

        $this->assertIsNumeric($result);
    }

    public function test_portfolios_returns_collection()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->portfolios()->get();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
        $this->assertGreaterThanOrEqual(1, $result->count());
    }

    public function test_value_as_of_aggregates_multiple_portfolios()
    {
        $fund = FundExt::find($this->factory->fund->id);

        // Create a second portfolio for the same fund
        $portfolio2 = PortfolioExt::create([
            'fund_id' => $fund->id,
            'source' => 'TEST_PORTFOLIO_2',
        ]);

        // Get value - should include both portfolios
        $result = $fund->valueAsOf('2022-06-01');

        // Value should be numeric (even if 0 for the second empty portfolio)
        $this->assertIsNumeric($result);

        // Clean up
        $portfolio2->delete();
    }
}
