<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetPrice;
use App\Models\Fund;
use App\Models\Portfolio;
use App\Models\PortfolioAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for AssetPriceControllerExt
 * Target: Get coverage from 42% to 50%+
 */
class AssetPriceControllerExtTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $user;
    protected Asset $asset;
    protected Fund $fund;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();

        $this->user = $this->df->user;
        $this->fund = $this->df->fund;

        // Create an asset and some prices
        $this->asset = Asset::factory()->create([
            'name' => 'Test Asset',
            'type' => 'Stock',
        ]);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Index Tests ====================

    public function test_index_displays_asset_prices()
    {
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2023-01-01',
        ]);

        $response = $this->actingAs($this->user)->get(route('assetPrices.index'));

        $response->assertStatus(200);
        $response->assertViewIs('asset_prices.index');
        $response->assertViewHas('assetPrices');
        $response->assertViewHas('api');
    }

    public function test_index_filters_by_single_asset()
    {
        $asset2 = Asset::factory()->create(['name' => 'Asset 2']);

        AssetPrice::factory()->create(['asset_id' => $this->asset->id, 'price' => 100]);
        AssetPrice::factory()->create(['asset_id' => $asset2->id, 'price' => 200]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);
        $assetPrices = $response->viewData('assetPrices');
        $this->assertEquals(1, $assetPrices->total());
    }

    public function test_index_filters_by_multiple_assets()
    {
        $asset2 = Asset::factory()->create(['name' => 'Asset 2']);
        $asset3 = Asset::factory()->create(['name' => 'Asset 3']);

        AssetPrice::factory()->create(['asset_id' => $this->asset->id]);
        AssetPrice::factory()->create(['asset_id' => $asset2->id]);
        AssetPrice::factory()->create(['asset_id' => $asset3->id]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', [
                'asset_id' => [$this->asset->id, $asset2->id]
            ]));

        $response->assertStatus(200);
        $assetPrices = $response->viewData('assetPrices');
        $this->assertEquals(2, $assetPrices->total());
    }

    public function test_index_filters_by_fund()
    {
        // Create portfolio and link asset to fund
        $portfolio = Portfolio::factory()->create(['fund_id' => $this->fund->id]);
        PortfolioAsset::factory()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $this->asset->id,
        ]);

        AssetPrice::factory()->create(['asset_id' => $this->asset->id]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['fund_id' => $this->fund->id]));

        $response->assertStatus(200);
        $assetPrices = $response->viewData('assetPrices');
        $this->assertGreaterThanOrEqual(1, $assetPrices->total());
    }

    public function test_index_filters_by_date_range()
    {
        // Create prices with specific asset to filter
        $testAsset = Asset::factory()->create(['name' => 'Date Test Asset']);

        AssetPrice::factory()->create([
            'asset_id' => $testAsset->id,
            'start_dt' => '2023-01-01',
        ]);
        AssetPrice::factory()->create([
            'asset_id' => $testAsset->id,
            'start_dt' => '2023-06-01',
        ]);
        AssetPrice::factory()->create([
            'asset_id' => $testAsset->id,
            'start_dt' => '2023-12-01',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', [
                'asset_id' => $testAsset->id,
                'start_dt' => '2023-05-01',
                'end_dt' => '2023-07-01',
            ]));

        $response->assertStatus(200);
        $assetPrices = $response->viewData('assetPrices');
        // Should only get the June price
        $this->assertEquals(1, $assetPrices->total());
    }

    public function test_index_sorts_by_asset_name()
    {
        $assetA = Asset::factory()->create(['name' => 'Alpha Asset']);
        $assetB = Asset::factory()->create(['name' => 'Beta Asset']);

        AssetPrice::factory()->create(['asset_id' => $assetB->id]);
        AssetPrice::factory()->create(['asset_id' => $assetA->id]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['sort' => 'asset', 'dir' => 'asc']));

        $response->assertStatus(200);
        $assetPrices = $response->viewData('assetPrices');
        $first = $assetPrices->first();
        $this->assertEquals('Alpha Asset', $first->asset->name);
    }

    public function test_index_sorts_by_price()
    {
        // Create unique asset for this test
        $sortTestAsset = Asset::factory()->create(['name' => 'Sort Test Asset']);

        AssetPrice::factory()->create([
            'asset_id' => $sortTestAsset->id,
            'price' => 200,
            'start_dt' => now()->subMonths(2)->format('Y-m-d'),
        ]);
        AssetPrice::factory()->create([
            'asset_id' => $sortTestAsset->id,
            'price' => 100,
            'start_dt' => now()->subMonths(1)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', [
                'asset_id' => $sortTestAsset->id,
                'sort' => 'price',
                'dir' => 'asc'
            ]));

        $response->assertStatus(200);
        $assetPrices = $response->viewData('assetPrices');
        $first = $assetPrices->first();
        // Should get the lower price first
        $this->assertEquals(100, (float)$first->price);
    }

    public function test_index_generates_chart_data_for_single_asset()
    {
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100,
            'start_dt' => now()->subMonths(2)->format('Y-m-d'),
        ]);
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 110,
            'start_dt' => now()->subMonths(1)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);
        $chartData = $response->viewData('chartData');
        $this->assertNotNull($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
        $this->assertArrayHasKey('assetName', $chartData);
        $this->assertEquals($this->asset->name, $chartData['assetName']);
    }

    public function test_index_generates_multi_asset_chart_data()
    {
        $asset2 = Asset::factory()->create(['name' => 'Asset 2']);

        AssetPrice::factory()->create(['asset_id' => $this->asset->id, 'price' => 100]);
        AssetPrice::factory()->create(['asset_id' => $asset2->id, 'price' => 200]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', [
                'asset_id' => [$this->asset->id, $asset2->id]
            ]));

        $response->assertStatus(200);
        $chartData = $response->viewData('chartData');
        $this->assertNotNull($chartData);
        $this->assertArrayHasKey('datasets', $chartData);
        $this->assertArrayHasKey('multiAsset', $chartData);
        $this->assertTrue($chartData['multiAsset']);
        $this->assertEquals(2, count($chartData['datasets']));
    }

    public function test_index_no_chart_for_more_than_8_assets()
    {
        // Create 9 assets
        $assetIds = [$this->asset->id];
        for ($i = 0; $i < 8; $i++) {
            $asset = Asset::factory()->create(['name' => "Asset $i"]);
            $assetIds[] = $asset->id;
            AssetPrice::factory()->create(['asset_id' => $asset->id]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $assetIds]));

        $response->assertStatus(200);
        $chartData = $response->viewData('chartData');
        $this->assertNull($chartData);
    }

    public function test_index_filters_out_none_asset_id()
    {
        AssetPrice::factory()->create(['asset_id' => $this->asset->id]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => 'none']));

        $response->assertStatus(200);
        $assetPrices = $response->viewData('assetPrices');
        // Should show all prices (not filter by 'none')
        $this->assertGreaterThanOrEqual(1, $assetPrices->total());
    }

    // ==================== Create Tests ====================

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)->get(route('assetPrices.create'));

        $response->assertStatus(200);
        $response->assertViewIs('asset_prices.create');
        $response->assertViewHas('api');
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form()
    {
        $assetPrice = AssetPrice::factory()->create(['asset_id' => $this->asset->id]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.edit', $assetPrice->id));

        $response->assertStatus(200);
        $response->assertViewIs('asset_prices.edit');
        $response->assertViewHas('assetPrice');
        $response->assertViewHas('api');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.edit', 99999));

        $response->assertRedirect(route('assetPrices.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Negative Tests ====================

    public function test_index_handles_invalid_sort_direction()
    {
        AssetPrice::factory()->create(['asset_id' => $this->asset->id]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['sort' => 'price', 'dir' => 'invalid']));

        $response->assertStatus(200);
        // Should default to 'desc'
        $assetPrices = $response->viewData('assetPrices');
        $this->assertNotNull($assetPrices);
    }

    public function test_index_with_empty_asset_array()
    {
        AssetPrice::factory()->create(['asset_id' => $this->asset->id]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => []]));

        $response->assertStatus(200);
        $assetPrices = $response->viewData('assetPrices');
        $this->assertGreaterThanOrEqual(1, $assetPrices->total());
    }

    public function test_index_no_chart_when_no_prices_for_asset()
    {
        $emptyAsset = Asset::factory()->create(['name' => 'Empty Asset']);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $emptyAsset->id]));

        $response->assertStatus(200);
        $chartData = $response->viewData('chartData');
        $this->assertNull($chartData);
    }

    public function test_index_fund_chart_with_more_than_8_assets()
    {
        // Create portfolio with 9 assets
        $portfolio = Portfolio::factory()->create(['fund_id' => $this->fund->id]);
        for ($i = 0; $i < 9; $i++) {
            $asset = Asset::factory()->create(['name' => "Asset $i"]);
            PortfolioAsset::factory()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
            ]);
            AssetPrice::factory()->create(['asset_id' => $asset->id]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['fund_id' => $this->fund->id]));

        $response->assertStatus(200);
        $chartData = $response->viewData('chartData');
        // Should not generate chart for > 8 assets
        $this->assertNull($chartData);
    }

    public function test_index_with_date_filters_and_sorting()
    {
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'start_dt' => '2023-01-01',
            'end_dt' => '2023-01-31',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', [
                'start_dt' => '2023-01-01',
                'end_dt' => '2023-12-31',
                'sort' => 'end_dt',
                'dir' => 'asc',
            ]));

        $response->assertStatus(200);
        $assetPrices = $response->viewData('assetPrices');
        $this->assertGreaterThanOrEqual(1, $assetPrices->total());
    }
}
