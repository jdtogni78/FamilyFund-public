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
}
