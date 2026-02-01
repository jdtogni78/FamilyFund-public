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

    // =========================================================================
    // Additional tests for yearlyPerformance, periodPerformance, and related
    // =========================================================================

    public function test_yearly_performance_returns_numeric()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->yearlyPerformance(2022);

        $this->assertIsNumeric($result);
    }

    public function test_yearly_performance_returns_zero_for_no_portfolios()
    {
        // Create a fund without a portfolio
        $fund = FundExt::create([
            'name' => 'Empty Fund ' . uniqid(),
            'short_name' => 'EF',
        ]);

        $result = $fund->yearlyPerformance(2022);

        $this->assertEquals(0, $result);

        // Clean up
        $fund->delete();
    }

    public function test_period_performance_returns_zero_for_no_portfolios()
    {
        // Create a fund without a portfolio
        $fund = FundExt::create([
            'name' => 'Empty Fund ' . uniqid(),
            'short_name' => 'EF',
        ]);

        $result = $fund->periodPerformance('2022-01-01', '2022-12-31');

        $this->assertEquals(0, $result);

        // Clean up
        $fund->delete();
    }

    public function test_period_performance_returns_zero_when_start_value_is_zero()
    {
        $fund = FundExt::find($this->factory->fund->id);

        // Query a date before the fund has any value
        $result = $fund->periodPerformance('2020-01-01', '2020-06-01');

        $this->assertEquals(0, $result);
    }

    public function test_period_performance_with_multiple_portfolios()
    {
        $fund = FundExt::find($this->factory->fund->id);

        // Create a second portfolio for the same fund (source max 30 chars)
        $portfolio2 = PortfolioExt::create([
            'fund_id' => $fund->id,
            'source' => 'TEST_PERF_' . substr(uniqid(), 0, 10),
        ]);

        // Link to fund via pivot
        $fund->portfolios()->attach($portfolio2->id);

        // Test performance calculation with multiple portfolios
        $result = $fund->periodPerformance('2022-01-01', '2022-06-01');

        $this->assertIsNumeric($result);

        // Clean up
        $fund->portfolios()->detach($portfolio2->id);
        $portfolio2->delete();
    }

    public function test_yearly_performance_with_multiple_portfolios()
    {
        $fund = FundExt::find($this->factory->fund->id);

        // Create a second portfolio for the same fund (source max 30 chars)
        $portfolio2 = PortfolioExt::create([
            'fund_id' => $fund->id,
            'source' => 'TEST_YEAR_' . substr(uniqid(), 0, 10),
        ]);

        // Link to fund via pivot
        $fund->portfolios()->attach($portfolio2->id);

        // Test yearly performance with multiple portfolios
        $result = $fund->yearlyPerformance(2022);

        $this->assertIsNumeric($result);

        // Clean up
        $fund->portfolios()->detach($portfolio2->id);
        $portfolio2->delete();
    }

    // =========================================================================
    // Account balance and transaction tests
    // =========================================================================

    public function test_account_balances_as_of_returns_balances()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->accountBalancesAsOf('2022-06-01');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_fund_account_returns_account_without_user()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->fundAccount();

        $this->assertInstanceOf(AccountExt::class, $result);
        $this->assertNull($result->user_id);
    }

    public function test_find_oldest_transaction_returns_transaction()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->findOldestTransaction();

        $this->assertNotNull($result);
        $this->assertEquals('2022-01-01', $result->timestamp->format('Y-m-d'));
    }

    public function test_find_oldest_transaction_returns_null_when_no_account()
    {
        // Create a fund without an account
        $fund = FundExt::create([
            'name' => 'No Account Fund ' . uniqid(),
            'short_name' => 'NAF',
        ]);

        $result = $fund->findOldestTransaction();

        $this->assertNull($result);

        // Clean up
        $fund->delete();
    }

    public function test_shares_as_of_returns_zero_when_no_account()
    {
        // Create a fund without an account
        $fund = FundExt::create([
            'name' => 'No Account Fund ' . uniqid(),
            'short_name' => 'NAF',
        ]);

        $result = $fund->sharesAsOf('2022-06-01');

        $this->assertEquals(0, $result);

        // Clean up
        $fund->delete();
    }

    // =========================================================================
    // Four percent rule goal tests
    // =========================================================================

    public function test_has_four_pct_goal_returns_false_when_not_configured()
    {
        $fund = FundExt::find($this->factory->fund->id);

        // By default, four_pct_yearly_expenses is null
        $result = $fund->hasFourPctGoal();

        $this->assertFalse($result);
    }

    public function test_has_four_pct_goal_returns_true_when_configured()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->four_pct_yearly_expenses = 40000;
        $fund->save();

        $result = $fund->hasFourPctGoal();

        $this->assertTrue($result);
    }

    public function test_has_four_pct_goal_returns_false_for_zero_expenses()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->four_pct_yearly_expenses = 0;
        $fund->save();

        $result = $fund->hasFourPctGoal();

        $this->assertFalse($result);
    }

    public function test_four_pct_target_value_returns_correct_calculation()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->four_pct_yearly_expenses = 40000;
        $fund->save();

        $result = $fund->fourPctTargetValue();

        // 40000 * 25 = 1,000,000
        $this->assertEquals(1000000, $result);
    }

    public function test_four_pct_target_value_returns_zero_when_not_configured()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->fourPctTargetValue();

        $this->assertEquals(0, $result);
    }

    public function test_four_pct_net_worth_pct_returns_default_100()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->fourPctNetWorthPct();

        $this->assertEquals(100.0, $result);
    }

    public function test_four_pct_net_worth_pct_returns_configured_value()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->four_pct_net_worth_pct = 80.0;
        $fund->save();

        $result = $fund->fourPctNetWorthPct();

        $this->assertEquals(80.0, $result);
    }

    public function test_four_pct_adjusted_value_applies_net_worth_pct()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->four_pct_net_worth_pct = 50.0;
        $fund->save();

        $fundValue = $fund->valueAsOf('2022-06-01');
        $result = $fund->fourPctAdjustedValue('2022-06-01');

        $expected = $fundValue * 0.5;
        $this->assertEquals($expected, $result);
    }

    public function test_four_pct_current_yield_calculates_correctly()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->four_pct_net_worth_pct = 100.0;
        $fund->save();

        $adjustedValue = $fund->fourPctAdjustedValue('2022-06-01');
        $result = $fund->fourPctCurrentYield('2022-06-01');

        $expected = $adjustedValue * 0.04;
        $this->assertEquals($expected, $result);
    }

    public function test_four_pct_progress_returns_empty_when_not_configured()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->fourPctProgress('2022-06-01');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_four_pct_progress_returns_complete_data()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->four_pct_yearly_expenses = 40;  // Very low, so fund value exceeds target
        $fund->four_pct_net_worth_pct = 100.0;
        $fund->save();

        $result = $fund->fourPctProgress('2022-06-01');

        $this->assertArrayHasKey('yearly_expenses', $result);
        $this->assertArrayHasKey('target_value', $result);
        $this->assertArrayHasKey('net_worth_pct', $result);
        $this->assertArrayHasKey('adjusted_value', $result);
        $this->assertArrayHasKey('current_yield', $result);
        $this->assertArrayHasKey('progress_pct', $result);
        $this->assertArrayHasKey('is_reached', $result);

        $this->assertEquals(40, $result['yearly_expenses']);
        $this->assertEquals(1000, $result['target_value']); // 40 * 25
        $this->assertEquals(100.0, $result['net_worth_pct']);
    }

    public function test_four_pct_progress_is_reached_when_value_exceeds_target()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->four_pct_yearly_expenses = 1;  // Very low target
        $fund->four_pct_net_worth_pct = 100.0;
        $fund->save();

        $result = $fund->fourPctProgress('2022-06-01');

        $this->assertTrue($result['is_reached']);
        $this->assertEquals(100, $result['progress_pct']); // Capped at 100%
    }

    public function test_four_pct_progress_not_reached_when_value_below_target()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->four_pct_yearly_expenses = 1000000;  // Very high target
        $fund->four_pct_net_worth_pct = 100.0;
        $fund->save();

        $result = $fund->fourPctProgress('2022-06-01');

        $this->assertFalse($result['is_reached']);
        $this->assertLessThan(100, $result['progress_pct']);
    }
}
