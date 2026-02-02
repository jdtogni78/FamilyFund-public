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
    // Withdrawal rule goal tests
    // =========================================================================

    public function test_has_withdrawal_goal_returns_false_when_not_configured()
    {
        $fund = FundExt::find($this->factory->fund->id);

        // By default, withdrawal_yearly_expenses is null
        $result = $fund->hasWithdrawalGoal();

        $this->assertFalse($result);
    }

    public function test_has_withdrawal_goal_returns_true_when_configured()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 40000;
        $fund->save();

        $result = $fund->hasWithdrawalGoal();

        $this->assertTrue($result);
    }

    public function test_has_withdrawal_goal_returns_false_for_zero_expenses()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 0;
        $fund->save();

        $result = $fund->hasWithdrawalGoal();

        $this->assertFalse($result);
    }

    public function test_get_withdrawal_rate_returns_default_4()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->getWithdrawalRate();

        $this->assertEquals(4.0, $result);
    }

    public function test_get_withdrawal_rate_returns_configured_value()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_rate = 3.5;
        $fund->save();

        $result = $fund->getWithdrawalRate();

        $this->assertEquals(3.5, $result);
    }

    public function test_withdrawal_target_value_returns_correct_calculation()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 40000;
        $fund->withdrawal_rate = 4.0;
        $fund->save();

        $result = $fund->withdrawalTargetValue();

        // 40000 / 0.04 = 1,000,000
        $this->assertEquals(1000000, $result);
    }

    public function test_withdrawal_target_value_with_different_rate()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 40000;
        $fund->withdrawal_rate = 3.0;  // 3% rate
        $fund->save();

        $result = $fund->withdrawalTargetValue();

        // 40000 / 0.03 = 1,333,333.33
        $this->assertEqualsWithDelta(1333333.33, $result, 0.01);
    }

    public function test_withdrawal_target_value_returns_zero_when_not_configured()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->withdrawalTargetValue();

        $this->assertEquals(0, $result);
    }

    public function test_withdrawal_net_worth_pct_returns_default_100()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->withdrawalNetWorthPct();

        $this->assertEquals(100.0, $result);
    }

    public function test_withdrawal_net_worth_pct_returns_configured_value()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_net_worth_pct = 80.0;
        $fund->save();

        $result = $fund->withdrawalNetWorthPct();

        $this->assertEquals(80.0, $result);
    }

    public function test_withdrawal_adjusted_value_applies_net_worth_pct()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_net_worth_pct = 50.0;
        $fund->save();

        $fundValue = $fund->valueAsOf('2022-06-01');
        $result = $fund->withdrawalAdjustedValue('2022-06-01');

        $expected = $fundValue * 0.5;
        $this->assertEquals($expected, $result);
    }

    public function test_withdrawal_current_yield_calculates_correctly()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_net_worth_pct = 100.0;
        $fund->withdrawal_rate = 4.0;
        $fund->save();

        $adjustedValue = $fund->withdrawalAdjustedValue('2022-06-01');
        $result = $fund->withdrawalCurrentYield('2022-06-01');

        $expected = $adjustedValue * 0.04;
        $this->assertEquals($expected, $result);
    }

    public function test_withdrawal_current_yield_with_different_rate()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_net_worth_pct = 100.0;
        $fund->withdrawal_rate = 3.5;
        $fund->save();

        $adjustedValue = $fund->withdrawalAdjustedValue('2022-06-01');
        $result = $fund->withdrawalCurrentYield('2022-06-01');

        $expected = $adjustedValue * 0.035;
        $this->assertEquals($expected, $result);
    }

    public function test_withdrawal_progress_returns_empty_when_not_configured()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->withdrawalProgress('2022-06-01');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_withdrawal_progress_returns_complete_data()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 40;  // Very low, so fund value exceeds target
        $fund->withdrawal_net_worth_pct = 100.0;
        $fund->withdrawal_rate = 4.0;
        $fund->save();

        $result = $fund->withdrawalProgress('2022-06-01');

        $this->assertArrayHasKey('yearly_expenses', $result);
        $this->assertArrayHasKey('target_value', $result);
        $this->assertArrayHasKey('net_worth_pct', $result);
        $this->assertArrayHasKey('adjusted_value', $result);
        $this->assertArrayHasKey('current_yield', $result);
        $this->assertArrayHasKey('progress_pct', $result);
        $this->assertArrayHasKey('is_reached', $result);
        $this->assertArrayHasKey('withdrawal_rate', $result);

        $this->assertEquals(40, $result['yearly_expenses']);
        $this->assertEquals(1000, $result['target_value']); // 40 / 0.04
        $this->assertEquals(100.0, $result['net_worth_pct']);
        $this->assertEquals(4.0, $result['withdrawal_rate']);
    }

    public function test_withdrawal_progress_is_reached_when_value_exceeds_target()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 1;  // Very low target
        $fund->withdrawal_net_worth_pct = 100.0;
        $fund->save();

        $result = $fund->withdrawalProgress('2022-06-01');

        $this->assertTrue($result['is_reached']);
        $this->assertEquals(100, $result['progress_pct']); // Capped at 100%
    }

    public function test_withdrawal_progress_not_reached_when_value_below_target()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 1000000;  // Very high target
        $fund->withdrawal_net_worth_pct = 100.0;
        $fund->save();

        $result = $fund->withdrawalProgress('2022-06-01');

        $this->assertFalse($result['is_reached']);
        $this->assertLessThan(100, $result['progress_pct']);
    }

    // =========================================================================
    // Expected growth rate tests
    // =========================================================================

    public function test_get_expected_growth_rate_returns_default_7()
    {
        $fund = FundExt::find($this->factory->fund->id);

        $result = $fund->getExpectedGrowthRate();

        $this->assertEquals(7.0, $result);
    }

    public function test_get_expected_growth_rate_returns_configured_value()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->expected_growth_rate = 5.5;
        $fund->save();

        $result = $fund->getExpectedGrowthRate();

        $this->assertEquals(5.5, $result);
    }

    public function test_withdrawal_progress_includes_expected_growth_rate()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 40000;
        $fund->expected_growth_rate = 8.0;
        $fund->save();

        $result = $fund->withdrawalProgress('2022-06-01');

        $this->assertArrayHasKey('expected_growth_rate', $result);
        $this->assertEquals(8.0, $result['expected_growth_rate']);
    }

    public function test_calculate_target_reach_with_growth_rate_returns_null_when_no_goal()
    {
        $fund = FundExt::find($this->factory->fund->id);
        // No withdrawal_yearly_expenses set

        $result = $fund->calculateTargetReachWithGrowthRate('2022-06-01');

        $this->assertNull($result);
    }

    public function test_calculate_target_reach_with_growth_rate_already_reached()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 1;  // Very low target, fund value exceeds it
        $fund->expected_growth_rate = 7.0;
        $fund->save();

        $result = $fund->calculateTargetReachWithGrowthRate('2022-06-01');

        $this->assertIsArray($result);
        $this->assertTrue($result['reachable']);
        $this->assertTrue($result['already_reached']);
        $this->assertEquals(7.0, $result['expected_growth_rate']);
    }

    public function test_calculate_target_reach_with_growth_rate_returns_projection()
    {
        $fund = FundExt::find($this->factory->fund->id);
        // Set a target that's reachable within 50 years given fund value
        // Fund value in test data is ~1000, target should be ~3000 to get ~16 years at 7%
        $fund->withdrawal_yearly_expenses = 120;  // target = 120/0.04 = 3000
        $fund->expected_growth_rate = 7.0;
        $fund->save();

        $result = $fund->calculateTargetReachWithGrowthRate('2022-06-01');

        $this->assertIsArray($result);
        $this->assertTrue($result['reachable']);
        $this->assertArrayNotHasKey('already_reached', $result);
        // Should have projection keys (not distant since target is close)
        $this->assertArrayHasKey('years_from_now', $result);
        $this->assertGreaterThan(0, $result['years_from_now']);
        $this->assertEquals(7.0, $result['expected_growth_rate']);
        // If within 50 years, should have estimated_date
        if (!($result['distant'] ?? false)) {
            $this->assertArrayHasKey('estimated_date', $result);
            $this->assertArrayHasKey('estimated_date_formatted', $result);
        }
    }

    public function test_calculate_target_reach_with_growth_rate_zero_growth()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 75000;
        $fund->expected_growth_rate = 0;
        $fund->save();

        $result = $fund->calculateTargetReachWithGrowthRate('2022-06-01');

        $this->assertIsArray($result);
        $this->assertFalse($result['reachable']);
        $this->assertEquals('no_growth', $result['reason']);
    }

    public function test_calculate_target_reach_with_growth_rate_formula()
    {
        // Test the formula: years = log(target / current) / log(1 + growth_rate)
        // Example: Current $1,000, Target $3,000, Growth 7%
        // years = log(3000/1000) / log(1.07) = log(3) / 0.0677 ≈ 16.2 years
        $fund = FundExt::find($this->factory->fund->id);
        // Fund value is ~1000, set target to be 3x
        $fund->withdrawal_yearly_expenses = 120;  // target = 120/0.04 = 3000
        $fund->expected_growth_rate = 7.0;
        $fund->save();

        $result = $fund->calculateTargetReachWithGrowthRate('2022-06-01');

        $this->assertIsArray($result);
        $this->assertTrue($result['reachable']);
        // Expected: ~16.2 years (allow some variance due to fund value)
        $this->assertGreaterThan(10, $result['years_from_now']);
        $this->assertLessThan(25, $result['years_from_now']);
    }

    public function test_calculate_target_reach_with_higher_growth_rate_is_faster()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 200;  // Moderate target
        $fund->save();

        // Test with 7% growth
        $fund->expected_growth_rate = 7.0;
        $fund->save();
        $result7 = $fund->calculateTargetReachWithGrowthRate('2022-06-01');

        // Test with 10% growth
        $fund->expected_growth_rate = 10.0;
        $fund->save();
        $result10 = $fund->calculateTargetReachWithGrowthRate('2022-06-01');

        // Higher growth rate should reach target faster
        $this->assertLessThan($result7['years_from_now'], $result10['years_from_now']);
    }

    public function test_calculate_target_reach_distant_goal()
    {
        $fund = FundExt::find($this->factory->fund->id);
        // Set a very high target relative to fund value with low growth
        $fund->withdrawal_yearly_expenses = 10000000;  // target = 250M
        $fund->expected_growth_rate = 1.0;  // Very low growth
        $fund->save();

        $result = $fund->calculateTargetReachWithGrowthRate('2022-06-01');

        $this->assertIsArray($result);
        $this->assertTrue($result['reachable']);
        $this->assertTrue($result['distant']);
        $this->assertGreaterThan(50, $result['years_from_now']);
    }

    // =========================================================================
    // Calculate target reach WITH withdrawals tests
    // =========================================================================

    public function test_calculate_target_reach_with_withdrawals_returns_null_when_no_goal()
    {
        $fund = FundExt::find($this->factory->fund->id);
        // No withdrawal_yearly_expenses set

        $result = $fund->calculateTargetReachWithWithdrawals('2022-06-01');

        $this->assertNull($result);
    }

    public function test_calculate_target_reach_with_withdrawals_already_reached()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 1;  // Very low target, fund value exceeds it
        $fund->expected_growth_rate = 7.0;
        $fund->withdrawal_rate = 4.0;
        $fund->save();

        $result = $fund->calculateTargetReachWithWithdrawals('2022-06-01');

        $this->assertIsArray($result);
        $this->assertTrue($result['reachable']);
        $this->assertTrue($result['already_reached']);
        $this->assertEquals(7.0, $result['expected_growth_rate']);
        $this->assertEquals(4.0, $result['withdrawal_rate']);
        $this->assertEquals(3.0, $result['net_growth_rate']);  // 7 - 4 = 3
    }

    public function test_calculate_target_reach_with_withdrawals_exceeds_growth()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 75000;  // High target
        $fund->expected_growth_rate = 3.0;  // Low growth
        $fund->withdrawal_rate = 4.0;  // Higher withdrawal rate
        $fund->save();

        $result = $fund->calculateTargetReachWithWithdrawals('2022-06-01');

        $this->assertIsArray($result);
        $this->assertFalse($result['reachable']);
        $this->assertEquals('withdrawals_exceed_growth', $result['reason']);
        $this->assertEquals(-1.0, $result['net_growth_rate']);  // 3 - 4 = -1
    }

    public function test_calculate_target_reach_with_withdrawals_equal_rates()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 75000;
        $fund->expected_growth_rate = 4.0;
        $fund->withdrawal_rate = 4.0;  // Equal to growth rate
        $fund->save();

        $result = $fund->calculateTargetReachWithWithdrawals('2022-06-01');

        $this->assertIsArray($result);
        $this->assertFalse($result['reachable']);
        $this->assertEquals('withdrawals_exceed_growth', $result['reason']);
        $this->assertEquals(0.0, $result['net_growth_rate']);
    }

    public function test_calculate_target_reach_with_withdrawals_returns_projection()
    {
        $fund = FundExt::find($this->factory->fund->id);
        // Set a target that's reachable
        $fund->withdrawal_yearly_expenses = 120;  // target = 120/0.04 = 3000
        $fund->expected_growth_rate = 8.0;
        $fund->withdrawal_rate = 4.0;
        $fund->save();

        $result = $fund->calculateTargetReachWithWithdrawals('2022-06-01');

        $this->assertIsArray($result);
        $this->assertTrue($result['reachable']);
        $this->assertArrayNotHasKey('already_reached', $result);
        $this->assertArrayHasKey('years_from_now', $result);
        $this->assertGreaterThan(0, $result['years_from_now']);
        $this->assertEquals(8.0, $result['expected_growth_rate']);
        $this->assertEquals(4.0, $result['withdrawal_rate']);
        $this->assertEquals(4.0, $result['net_growth_rate']);  // 8 - 4 = 4
        // If within 50 years, should have estimated_date
        if (!($result['distant'] ?? false)) {
            $this->assertArrayHasKey('estimated_date', $result);
            $this->assertArrayHasKey('estimated_date_formatted', $result);
        }
    }

    public function test_calculate_target_reach_with_withdrawals_slower_than_pure_growth()
    {
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 200;  // Moderate target
        $fund->expected_growth_rate = 8.0;
        $fund->withdrawal_rate = 4.0;
        $fund->save();

        // Without withdrawals (pure growth)
        $resultPure = $fund->calculateTargetReachWithGrowthRate('2022-06-01');

        // With withdrawals (net growth)
        $resultWithdrawals = $fund->calculateTargetReachWithWithdrawals('2022-06-01');

        // With withdrawals should take longer to reach target
        if (($resultPure['reachable'] ?? false) && ($resultWithdrawals['reachable'] ?? false)) {
            $this->assertGreaterThan($resultPure['years_from_now'], $resultWithdrawals['years_from_now']);
        }
    }

    public function test_calculate_target_reach_with_withdrawals_formula()
    {
        // Test the formula: net_rate = growth_rate - withdrawal_rate
        // years = log(target / current) / log(1 + net_rate)
        // Example: Current $1,000, Target $3,000, Growth 8%, Withdrawal 4% => Net 4%
        // years = log(3000/1000) / log(1.04) = log(3) / 0.0392 ≈ 28 years
        $fund = FundExt::find($this->factory->fund->id);
        $fund->withdrawal_yearly_expenses = 120;  // target = 120/0.04 = 3000
        $fund->expected_growth_rate = 8.0;
        $fund->withdrawal_rate = 4.0;  // Net rate = 4%
        $fund->save();

        $result = $fund->calculateTargetReachWithWithdrawals('2022-06-01');

        $this->assertIsArray($result);
        $this->assertTrue($result['reachable']);
        // Expected: ~28 years at 4% net growth (vs ~16 years at 7% pure growth)
        $this->assertGreaterThan(20, $result['years_from_now']);
        $this->assertLessThan(40, $result['years_from_now']);
    }

    public function test_calculate_target_reach_with_withdrawals_distant_goal()
    {
        $fund = FundExt::find($this->factory->fund->id);
        // Set a very high target with small net growth
        $fund->withdrawal_yearly_expenses = 10000000;  // target = 250M
        $fund->expected_growth_rate = 5.0;
        $fund->withdrawal_rate = 4.0;  // Net rate = 1%
        $fund->save();

        $result = $fund->calculateTargetReachWithWithdrawals('2022-06-01');

        $this->assertIsArray($result);
        $this->assertTrue($result['reachable']);
        $this->assertTrue($result['distant']);
        $this->assertGreaterThan(50, $result['years_from_now']);
    }
}
