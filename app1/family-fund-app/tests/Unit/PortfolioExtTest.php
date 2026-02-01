<?php

namespace Tests\Unit;

use App\Models\PortfolioExt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for PortfolioExt model
 */
class PortfolioExtTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
    }

    public function test_assets_as_of_returns_collection()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        $result = $portfolio->assetsAsOf('2022-06-01');

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_assets_as_of_with_asset_id_filter()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Filter by a non-existent asset ID should return empty
        $result = $portfolio->assetsAsOf('2022-06-01', 99999);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_trade_portfolios_between_returns_collection()
    {
        // First create a trade portfolio
        $this->factory->createTradePortfolio(\Carbon\Carbon::parse('2022-01-01'));

        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        $result = $portfolio->tradePortfoliosBetween('2022-01-01', '2022-12-31');

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_asset_history_returns_collection()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        $result = $portfolio->assetHistory(1);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_value_as_of_returns_float()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        $result = $portfolio->valueAsOf('2022-06-01');

        $this->assertIsFloat($result);
    }

    public function test_value_as_of_calculates_total()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Portfolio was created with fund value of 1000
        $result = $portfolio->valueAsOf('2022-06-01');

        // Should have some value
        $this->assertGreaterThanOrEqual(0, $result);
    }

    // =========================================================================
    // Type and Category Tests
    // =========================================================================

    public function test_get_category_from_type_returns_correct_category()
    {
        // Test retirement types
        $this->assertEquals('retirement', PortfolioExt::getCategoryFromType('401k'));
        $this->assertEquals('retirement', PortfolioExt::getCategoryFromType('ira'));
        $this->assertEquals('retirement', PortfolioExt::getCategoryFromType('roth_ira'));
        $this->assertEquals('retirement', PortfolioExt::getCategoryFromType('pension'));

        // Test taxable types
        $this->assertEquals('taxable', PortfolioExt::getCategoryFromType('brokerage'));
        $this->assertEquals('taxable', PortfolioExt::getCategoryFromType('real_estate'));
        $this->assertEquals('taxable', PortfolioExt::getCategoryFromType('vehicle'));

        // Test education types
        $this->assertEquals('education', PortfolioExt::getCategoryFromType('529'));

        // Test liability types
        $this->assertEquals('liability', PortfolioExt::getCategoryFromType('mortgage'));
        $this->assertEquals('liability', PortfolioExt::getCategoryFromType('loan'));
        $this->assertEquals('liability', PortfolioExt::getCategoryFromType('credit_card'));

        // Test cash types
        $this->assertEquals('cash', PortfolioExt::getCategoryFromType('checking'));
        $this->assertEquals('cash', PortfolioExt::getCategoryFromType('savings'));
    }

    public function test_get_category_from_type_returns_null_for_unknown()
    {
        $this->assertNull(PortfolioExt::getCategoryFromType('unknown_type'));
        $this->assertNull(PortfolioExt::getCategoryFromType(''));
        $this->assertNull(PortfolioExt::getCategoryFromType(null));
    }

    public function test_get_type_label_returns_formatted_label()
    {
        $portfolio = new PortfolioExt();

        $portfolio->type = '401k';
        $this->assertEquals('401(k)', $portfolio->getTypeLabel());

        $portfolio->type = 'roth_ira';
        $this->assertEquals('Roth IRA', $portfolio->getTypeLabel());

        $portfolio->type = 'brokerage';
        $this->assertEquals('Brokerage', $portfolio->getTypeLabel());

        $portfolio->type = 'real_estate';
        $this->assertEquals('Real Estate', $portfolio->getTypeLabel());
    }

    public function test_get_type_label_returns_ucfirst_for_unknown()
    {
        $portfolio = new PortfolioExt();

        $portfolio->type = 'custom_type';
        $this->assertEquals('Custom_type', $portfolio->getTypeLabel());

        $portfolio->type = null;
        $this->assertEquals('Unknown', $portfolio->getTypeLabel());
    }

    public function test_get_category_label_returns_formatted_label()
    {
        $portfolio = new PortfolioExt();

        $portfolio->category = 'retirement';
        $this->assertEquals('Retirement', $portfolio->getCategoryLabel());

        $portfolio->category = 'taxable';
        $this->assertEquals('Taxable', $portfolio->getCategoryLabel());

        $portfolio->category = 'liability';
        $this->assertEquals('Liability', $portfolio->getCategoryLabel());
    }

    public function test_get_category_color_returns_hex_color()
    {
        $portfolio = new PortfolioExt();

        $portfolio->category = 'retirement';
        $this->assertEquals('#7c3aed', $portfolio->getCategoryColor());

        $portfolio->category = 'taxable';
        $this->assertEquals('#059669', $portfolio->getCategoryColor());

        $portfolio->category = 'liability';
        $this->assertEquals('#dc2626', $portfolio->getCategoryColor());

        $portfolio->category = 'cash';
        $this->assertEquals('#6b7280', $portfolio->getCategoryColor());
    }

    public function test_get_category_color_returns_default_for_unknown()
    {
        $portfolio = new PortfolioExt();

        $portfolio->category = 'unknown';
        $this->assertEquals('#6b7280', $portfolio->getCategoryColor());

        $portfolio->category = null;
        $this->assertEquals('#6b7280', $portfolio->getCategoryColor());
    }

    public function test_is_property_type_returns_true_for_property_types()
    {
        $portfolio = new PortfolioExt();

        $portfolio->type = 'real_estate';
        $this->assertTrue($portfolio->isPropertyType());

        $portfolio->type = 'vehicle';
        $this->assertTrue($portfolio->isPropertyType());

        $portfolio->type = 'mortgage';
        $this->assertTrue($portfolio->isPropertyType());

        $portfolio->type = 'loan';
        $this->assertTrue($portfolio->isPropertyType());
    }

    public function test_is_property_type_returns_false_for_non_property_types()
    {
        $portfolio = new PortfolioExt();

        $portfolio->type = 'brokerage';
        $this->assertFalse($portfolio->isPropertyType());

        $portfolio->type = '401k';
        $this->assertFalse($portfolio->isPropertyType());

        $portfolio->type = 'checking';
        $this->assertFalse($portfolio->isPropertyType());

        $portfolio->type = 'credit_card';
        $this->assertFalse($portfolio->isPropertyType());

        $portfolio->type = null;
        $this->assertFalse($portfolio->isPropertyType());
    }

    // =========================================================================
    // Balance and Value Calculation Tests
    // =========================================================================

    public function test_balance_as_of_returns_null_when_no_balance()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // No balance set for this date
        $result = $portfolio->balanceAsOf('2022-06-01');

        $this->assertNull($result);
    }

    public function test_balance_as_of_returns_balance_when_set()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Create a portfolio balance
        \App\Models\PortfolioBalance::create([
            'portfolio_id' => $portfolio->id,
            'balance' => 5000.00,
            'start_dt' => '2022-01-01',
            'end_dt' => '2022-12-31',
        ]);

        $result = $portfolio->balanceAsOf('2022-06-01');

        $this->assertNotNull($result);
        $this->assertEquals(5000.00, $result->balance);
    }

    public function test_calculate_value_from_assets_returns_float()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        $result = $portfolio->calculateValueFromAssets('2022-06-01');

        $this->assertIsFloat($result);
    }

    public function test_calculate_value_from_assets_includes_cash_position()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // The factory creates a cash position of 1000
        $result = $portfolio->calculateValueFromAssets('2022-06-01');

        $this->assertEquals(1000.0, $result);
    }

    public function test_calculate_value_from_assets_negates_liability()
    {
        // Create a liability portfolio
        $portfolio = PortfolioExt::create([
            'fund_id' => $this->factory->fund->id,
            'source' => 'LIABILITY_' . substr(uniqid(), 0, 8),
            'category' => PortfolioExt::CATEGORY_LIABILITY,
            'type' => PortfolioExt::TYPE_MORTGAGE,
        ]);

        // Add a cash asset to represent the debt
        $cash = \App\Models\AssetExt::getCashAsset();
        $portfolioAsset = \App\Models\PortfolioAsset::create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $cash->id,
            'position' => 100000, // $100k debt
            'start_dt' => '2022-01-01',
            'end_dt' => '9999-12-31',
        ]);

        $result = $portfolio->calculateValueFromAssets('2022-06-01');

        // Liability should be negative
        $this->assertEquals(-100000.0, $result);

        // Clean up - delete portfolio asset first due to FK constraint
        $portfolioAsset->delete();
        $portfolio->forceDelete();
    }

    public function test_value_as_of_uses_set_balance_when_available()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Create a set balance
        \App\Models\PortfolioBalance::create([
            'portfolio_id' => $portfolio->id,
            'balance' => 7500.00,
            'start_dt' => '2022-01-01',
            'end_dt' => '2022-12-31',
        ]);

        $result = $portfolio->valueAsOf('2022-06-01');

        // Should return the set balance, not calculated
        $this->assertEquals(7500.0, $result);
    }

    public function test_value_as_of_with_validate_returns_array()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        $result = $portfolio->valueAsOf('2022-06-01', true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('set_balance', $result);
        $this->assertArrayHasKey('calculated', $result);
        $this->assertArrayHasKey('difference', $result);
        $this->assertArrayHasKey('percent_diff', $result);
        $this->assertArrayHasKey('has_set_balance', $result);
        $this->assertArrayHasKey('is_valid', $result);
    }

    public function test_value_as_of_validate_with_set_balance()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Create a set balance
        \App\Models\PortfolioBalance::create([
            'portfolio_id' => $portfolio->id,
            'balance' => 1000.00, // Same as calculated to be valid
            'start_dt' => '2022-01-01',
            'end_dt' => '2022-12-31',
        ]);

        $result = $portfolio->valueAsOf('2022-06-01', true);

        $this->assertTrue($result['has_set_balance']);
        $this->assertEquals(1000.0, $result['set_balance']);
        $this->assertEquals(1000.0, $result['calculated']);
        $this->assertEquals(0, $result['difference']);
        $this->assertTrue($result['is_valid']);
    }

    public function test_value_as_of_validate_detects_invalid_difference()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Create a set balance with large difference (>5%)
        \App\Models\PortfolioBalance::create([
            'portfolio_id' => $portfolio->id,
            'balance' => 2000.00, // 100% different from calculated 1000
            'start_dt' => '2022-01-01',
            'end_dt' => '2022-12-31',
        ]);

        $result = $portfolio->valueAsOf('2022-06-01', true);

        $this->assertTrue($result['has_set_balance']);
        $this->assertFalse($result['is_valid']); // >5% difference
        $this->assertEquals(100.0, $result['percent_diff']); // 100% difference
    }

    // =========================================================================
    // Performance Calculation Tests
    // =========================================================================

    public function test_period_performance_returns_numeric()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        $result = $portfolio->periodPerformance('2022-01-01', '2022-06-01');

        $this->assertIsNumeric($result);
    }

    public function test_period_performance_returns_zero_when_start_value_zero()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Query dates before any value exists
        $result = $portfolio->periodPerformance('2020-01-01', '2020-06-01');

        $this->assertEquals(0, $result);
    }

    public function test_yearly_performance_returns_numeric()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        $result = $portfolio->yearlyPerformance(2022);

        $this->assertIsNumeric($result);
    }

    public function test_yearly_performance_calculates_full_year()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Yearly performance should use Jan 1 to Jan 1 of next year
        $result = $portfolio->yearlyPerformance(2022);

        // The result depends on asset prices, but should be numeric
        $this->assertIsNumeric($result);
    }

    // =========================================================================
    // Max Cash Tests
    // =========================================================================

    public function test_max_cash_between_returns_float()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        $result = $portfolio->maxCashBetween('2022-01-01', '2022-12-31');

        $this->assertIsFloat($result);
    }

    public function test_max_cash_between_returns_max_position()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Factory creates a cash position of 1000
        $result = $portfolio->maxCashBetween('2022-01-01', '2022-12-31');

        $this->assertEquals(1000.0, $result);
    }

    public function test_max_cash_between_returns_zero_when_no_cash()
    {
        // Create a portfolio without cash
        $portfolio = PortfolioExt::create([
            'fund_id' => $this->factory->fund->id,
            'source' => 'NO_CASH_' . uniqid(),
        ]);

        $result = $portfolio->maxCashBetween('2022-01-01', '2022-12-31');

        $this->assertEquals(0.0, $result);

        // Clean up
        $portfolio->forceDelete();
    }

    // =========================================================================
    // Static Validation Tests
    // =========================================================================

    public function test_validate_all_balances_returns_structured_result()
    {
        $result = PortfolioExt::validateAllBalances('2022-06-01');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('as_of', $result);
        $this->assertArrayHasKey('threshold', $result);
        $this->assertArrayHasKey('has_errors', $result);
        $this->assertArrayHasKey('portfolios', $result);
    }

    public function test_validate_all_balances_includes_portfolio_with_balance()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Create a balance
        \App\Models\PortfolioBalance::create([
            'portfolio_id' => $portfolio->id,
            'balance' => 1000.00,
            'start_dt' => '2022-01-01',
            'end_dt' => '2022-12-31',
        ]);

        $result = PortfolioExt::validateAllBalances('2022-06-01');

        $this->assertIsArray($result['portfolios']);
        // Should find our portfolio
        $found = collect($result['portfolios'])->firstWhere('portfolio_id', $portfolio->id);
        $this->assertNotNull($found);
    }

    public function test_validate_all_balances_detects_errors()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Create a balance with large difference
        \App\Models\PortfolioBalance::create([
            'portfolio_id' => $portfolio->id,
            'balance' => 5000.00, // Very different from calculated 1000
            'start_dt' => '2022-01-01',
            'end_dt' => '2022-12-31',
        ]);

        $result = PortfolioExt::validateAllBalances('2022-06-01');

        $this->assertTrue($result['has_errors']);
    }

    public function test_validate_all_balances_with_custom_threshold()
    {
        $portfolio = PortfolioExt::find($this->factory->portfolio->id);

        // Create a balance with 3% difference
        \App\Models\PortfolioBalance::create([
            'portfolio_id' => $portfolio->id,
            'balance' => 1030.00, // 3% difference
            'start_dt' => '2022-01-01',
            'end_dt' => '2022-12-31',
        ]);

        // With default 5% threshold, should be valid
        $result = PortfolioExt::validateAllBalances('2022-06-01', 5.0);
        $this->assertFalse($result['has_errors']);

        // With 2% threshold, should be invalid
        $result = PortfolioExt::validateAllBalances('2022-06-01', 2.0);
        $this->assertTrue($result['has_errors']);
    }
}
