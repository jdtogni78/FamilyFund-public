<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Unit tests for AssetExt model
 */
class AssetExtTest extends TestCase
{
    use DatabaseTransactions;

    public function test_asset_map_returns_array_with_select_option()
    {
        $result = AssetExt::assetMap();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('none', $result);
        $this->assertEquals('Select Asset', $result['none']);
    }

    public function test_symbol_map_returns_array_with_select_option()
    {
        $result = AssetExt::symbolMap();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('none', $result);
        $this->assertEquals('Select Asset', $result['none']);
    }

    public function test_is_cash_input_returns_true_for_cash_name()
    {
        $input = ['name' => 'CASH', 'type' => 'STK'];
        $this->assertTrue(AssetExt::isCashInput($input));
    }

    public function test_is_cash_input_returns_true_for_csh_type()
    {
        $input = ['name' => 'Something', 'type' => 'CSH'];
        $this->assertTrue(AssetExt::isCashInput($input));
    }

    public function test_is_cash_input_returns_false_for_non_cash()
    {
        $input = ['name' => 'VOO', 'type' => 'STK'];
        $this->assertFalse(AssetExt::isCashInput($input));
    }

    public function test_is_cash_method_returns_true_for_cash_asset()
    {
        $asset = new AssetExt();
        $asset->name = 'CASH';
        $asset->type = 'CSH';

        $this->assertTrue($asset->isCash());
    }

    public function test_is_cash_method_returns_true_for_csh_type()
    {
        $asset = new AssetExt();
        $asset->name = 'Money Market';
        $asset->type = 'CSH';

        $this->assertTrue($asset->isCash());
    }

    public function test_is_cash_method_returns_false_for_stock()
    {
        $asset = new AssetExt();
        $asset->name = 'VOO';
        $asset->type = 'STK';

        $this->assertFalse($asset->isCash());
    }

    public function test_get_asset_finds_existing_asset()
    {
        // Create an asset
        $asset = Asset::factory()->create([
            'name' => 'TEST_ASSET_' . uniqid(),
            'type' => 'STK',
        ]);

        $result = AssetExt::getAsset($asset->name, 'STK');

        $this->assertEquals($asset->id, $result->id);
    }

    public function test_get_asset_throws_exception_for_nonexistent_asset()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cant find asset');

        AssetExt::getAsset('NONEXISTENT_' . uniqid(), 'STK');
    }

    public function test_price_as_of_returns_collection()
    {
        // Create an asset
        $asset = Asset::factory()->create([
            'name' => 'PRICE_TEST_' . uniqid(),
            'type' => 'STK',
        ]);

        // Create a price for the asset
        AssetPrice::factory()->create([
            'asset_id' => $asset->id,
            'price' => 100.50,
            'start_dt' => '2022-01-01',
            'end_dt' => '2099-12-31',
        ]);

        $assetExt = AssetExt::find($asset->id);
        $result = $assetExt->priceAsOf('2022-06-15');

        $this->assertCount(1, $result);
        $this->assertEquals(100.50, $result->first()->price);
    }

    public function test_price_as_of_returns_empty_when_no_price()
    {
        $asset = Asset::factory()->create([
            'name' => 'NO_PRICE_' . uniqid(),
            'type' => 'STK',
        ]);

        $assetExt = AssetExt::find($asset->id);
        $result = $assetExt->priceAsOf('2022-06-15');

        $this->assertCount(0, $result);
    }
}
