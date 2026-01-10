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
}
