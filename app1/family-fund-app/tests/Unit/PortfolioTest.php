<?php

namespace Tests\Unit;

use App\Models\FundExt;
use App\Models\Portfolio;
use App\Models\PortfolioAsset;
use App\Models\PortfolioBalance;
use App\Models\TradePortfolioExt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for Portfolio model (base class)
 */
class PortfolioTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
    }

    // =========================================================================
    // Basic Model Tests
    // =========================================================================

    public function test_portfolio_has_correct_table()
    {
        $portfolio = new Portfolio();
        $this->assertEquals('portfolios', $portfolio->getTable());
    }

    public function test_portfolio_has_correct_fillable_attributes()
    {
        $portfolio = new Portfolio();
        $fillable = $portfolio->getFillable();

        $this->assertContains('fund_id', $fillable);
        $this->assertContains('source', $fillable);
        $this->assertContains('display_name', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('category', $fillable);
    }

    public function test_portfolio_has_validation_rules()
    {
        $this->assertArrayHasKey('source', Portfolio::$rules);
        $this->assertArrayHasKey('fund_id', Portfolio::$rules);
    }

    // =========================================================================
    // Relationship Tests
    // =========================================================================

    public function test_fund_relationship_returns_fund()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        $result = $portfolio->fund;

        $this->assertInstanceOf(FundExt::class, $result);
        $this->assertEquals($this->factory->fund->id, $result->id);
    }

    public function test_funds_relationship_returns_collection()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        $result = $portfolio->funds;

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_portfolio_assets_relationship_returns_collection()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        $result = $portfolio->portfolioAssets;

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_portfolio_assets_relationship_includes_cash()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        // Factory creates a cash position
        $result = $portfolio->portfolioAssets;

        $this->assertGreaterThanOrEqual(1, $result->count());
    }

    public function test_trade_portfolios_relationship_returns_collection()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        $result = $portfolio->tradePortfolios;

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_trade_portfolios_includes_created_trade_portfolio()
    {
        // Create a trade portfolio
        $this->factory->createTradePortfolio(\Carbon\Carbon::parse('2022-01-01'));

        $portfolio = Portfolio::find($this->factory->portfolio->id);

        $result = $portfolio->tradePortfolios;

        $this->assertGreaterThanOrEqual(1, $result->count());
        $this->assertInstanceOf(TradePortfolioExt::class, $result->first());
    }

    public function test_portfolio_balances_relationship_returns_collection()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        $result = $portfolio->portfolioBalances;

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_portfolio_balances_includes_created_balance()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        // Create a balance
        PortfolioBalance::create([
            'portfolio_id' => $portfolio->id,
            'balance' => 5000.00,
            'start_dt' => '2022-01-01',
            'end_dt' => '2022-12-31',
        ]);

        $result = $portfolio->portfolioBalances()->get();

        $this->assertGreaterThanOrEqual(1, $result->count());
        $this->assertEquals(5000.00, $result->first()->balance);
    }

    // =========================================================================
    // getPrimaryFund Tests
    // =========================================================================

    public function test_get_primary_fund_returns_fund_from_legacy_fund_id()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        $result = $portfolio->getPrimaryFund();

        $this->assertInstanceOf(FundExt::class, $result);
        $this->assertEquals($this->factory->fund->id, $result->id);
    }

    public function test_get_primary_fund_returns_fund_from_pivot_table()
    {
        // Create a portfolio linked via pivot
        $portfolio = Portfolio::create([
            'fund_id' => null,
            'source' => 'PIVOT_TEST_' . uniqid(),
        ]);
        $portfolio->funds()->attach($this->factory->fund->id);

        $result = $portfolio->getPrimaryFund();

        $this->assertInstanceOf(FundExt::class, $result);
        $this->assertEquals($this->factory->fund->id, $result->id);

        // Clean up
        $portfolio->funds()->detach();
        $portfolio->forceDelete();
    }

    public function test_get_primary_fund_returns_null_when_no_fund()
    {
        // Create a portfolio without fund
        $portfolio = Portfolio::create([
            'fund_id' => null,
            'source' => 'NO_FUND_' . uniqid(),
        ]);

        $result = $portfolio->getPrimaryFund();

        $this->assertNull($result);

        // Clean up
        $portfolio->forceDelete();
    }

    // =========================================================================
    // Soft Delete Tests
    // =========================================================================

    public function test_portfolio_can_be_soft_deleted()
    {
        $portfolio = Portfolio::create([
            'fund_id' => $this->factory->fund->id,
            'source' => 'SOFT_DELETE_TEST_' . uniqid(),
        ]);

        $portfolioId = $portfolio->id;
        $portfolio->delete();

        // Should not be found in normal query
        $this->assertNull(Portfolio::find($portfolioId));

        // Should be found with trashed
        $this->assertNotNull(Portfolio::withTrashed()->find($portfolioId));

        // Clean up
        Portfolio::withTrashed()->find($portfolioId)->forceDelete();
    }

    public function test_portfolio_can_be_restored()
    {
        $portfolio = Portfolio::create([
            'fund_id' => $this->factory->fund->id,
            'source' => 'RESTORE_TEST_' . uniqid(),
        ]);

        $portfolioId = $portfolio->id;
        $portfolio->delete();

        // Restore
        Portfolio::withTrashed()->find($portfolioId)->restore();

        // Should be found now
        $this->assertNotNull(Portfolio::find($portfolioId));

        // Clean up
        Portfolio::find($portfolioId)->forceDelete();
    }

    // =========================================================================
    // Factory Tests
    // =========================================================================

    public function test_portfolio_factory_creates_valid_portfolio()
    {
        $portfolio = Portfolio::factory()->create([
            'fund_id' => $this->factory->fund->id,
        ]);

        $this->assertNotNull($portfolio->id);
        $this->assertNotNull($portfolio->source);
        $this->assertEquals($this->factory->fund->id, $portfolio->fund_id);

        // Clean up
        $portfolio->forceDelete();
    }

    public function test_portfolio_factory_can_set_type_and_category()
    {
        $portfolio = Portfolio::factory()->create([
            'fund_id' => $this->factory->fund->id,
            'type' => '401k',
            'category' => 'retirement',
        ]);

        $this->assertEquals('401k', $portfolio->type);
        $this->assertEquals('retirement', $portfolio->category);

        // Clean up
        $portfolio->forceDelete();
    }

    // =========================================================================
    // Cast Tests
    // =========================================================================

    public function test_portfolio_casts_id_to_integer()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        $this->assertIsInt($portfolio->id);
    }

    public function test_portfolio_casts_fund_id_to_integer()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        $this->assertIsInt($portfolio->fund_id);
    }

    public function test_portfolio_casts_source_to_string()
    {
        $portfolio = Portfolio::find($this->factory->portfolio->id);

        $this->assertIsString($portfolio->source);
    }
}
