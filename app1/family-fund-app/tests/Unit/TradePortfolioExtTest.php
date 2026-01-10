<?php

namespace Tests\Unit;

use App\Models\TradePortfolio;
use App\Models\TradePortfolioExt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for TradePortfolioExt model
 */
class TradePortfolioExtTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
    }

    public function test_port_map_returns_array_with_select_option()
    {
        $this->factory->createTradePortfolio(Carbon::today());

        $result = TradePortfolioExt::portMap();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('none', $result);
        $this->assertEquals('Select Portfolio', $result['none']);
    }

    public function test_port_map_includes_trade_portfolios()
    {
        $this->factory->createTradePortfolio(Carbon::today());

        $result = TradePortfolioExt::portMap();

        // Should include the trade portfolio we just created
        $this->assertGreaterThan(1, count($result));
    }

    public function test_previous_returns_null_when_no_previous()
    {
        $this->factory->createTradePortfolio(Carbon::today());
        $tp = TradePortfolioExt::find($this->factory->tradePortfolio->id);

        $result = $tp->previous();

        $this->assertNull($result);
    }

    public function test_split_rules_defined()
    {
        $this->assertArrayHasKey('start_dt', TradePortfolioExt::$split_rules);
        $this->assertArrayHasKey('end_dt', TradePortfolioExt::$split_rules);
    }

    public function test_split_with_items_validates_start_date_after_today()
    {
        $this->factory->createTradePortfolio(Carbon::today());
        $tp = TradePortfolioExt::find($this->factory->tradePortfolio->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Start date');

        // Start date in past
        $tp->splitWithItems(Carbon::yesterday(), Carbon::parse('9999-12-31'));
    }

    public function test_split_with_items_validates_start_date_after_previous_start()
    {
        $this->factory->createTradePortfolio(Carbon::today());
        $tp = TradePortfolioExt::find($this->factory->tradePortfolio->id);
        $tp->start_dt = Carbon::today()->addDays(5);
        $tp->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('must be greater than previous start date');

        // Start date same as current start date
        $tp->splitWithItems(Carbon::today()->addDays(5), Carbon::parse('9999-12-31'));
    }

    public function test_split_with_items_validates_end_date_after_today()
    {
        $this->factory->createTradePortfolio(Carbon::today());
        $tp = TradePortfolioExt::find($this->factory->tradePortfolio->id);
        $tp->start_dt = Carbon::today();
        $tp->end_dt = Carbon::parse('9999-12-31');
        $tp->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('End date');

        // End date in past
        $tp->splitWithItems(Carbon::tomorrow(), Carbon::yesterday());
    }

    public function test_split_with_items_validates_end_date_after_start()
    {
        $this->factory->createTradePortfolio(Carbon::today());
        $tp = TradePortfolioExt::find($this->factory->tradePortfolio->id);
        $tp->start_dt = Carbon::today();
        $tp->end_dt = Carbon::parse('9999-12-31');
        $tp->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('must be greater than start date');

        // End date before start date (both in future)
        $tp->splitWithItems(Carbon::today()->addDays(10), Carbon::today()->addDays(5));
    }
}
