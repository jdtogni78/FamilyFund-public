<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Asset;
use App\Models\AssetPrice;

use PHPUnit\Framework\Attributes\Test;
class AssetPriceApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_index_asset_prices()
    {
        // Create multiple asset prices
        $assetPrice1 = AssetPrice::factory()->create();
        $assetPrice2 = AssetPrice::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/asset_prices'
        );

        $this->response->assertStatus(200);
        $this->response->assertJson(['success' => true]);
        $responseData = $this->response->json('data');
        $this->assertIsArray($responseData);
        $this->assertGreaterThanOrEqual(2, count($responseData));
    }

    #[Test]
    public function test_index_asset_prices_with_pagination()
    {
        // Create multiple asset prices
        AssetPrice::factory()->count(5)->create();

        // Test with skip and limit
        $this->response = $this->json(
            'GET',
            '/api/asset_prices?skip=1&limit=2'
        );

        $this->response->assertStatus(200);
        $this->response->assertJson(['success' => true]);
        $responseData = $this->response->json('data');
        $this->assertIsArray($responseData);
        $this->assertLessThanOrEqual(2, count($responseData));
    }

    #[Test]
    public function test_index_asset_prices_with_filter()
    {
        // Create asset with specific price
        $asset = Asset::factory()->create();
        $assetPrice = AssetPrice::factory()->create(['asset_id' => $asset->id]);

        // Test filtering by asset_id
        $this->response = $this->json(
            'GET',
            '/api/asset_prices?asset_id=' . $asset->id
        );

        $this->response->assertStatus(200);
        $this->response->assertJson(['success' => true]);
        $responseData = $this->response->json('data');
        $this->assertIsArray($responseData);
        $this->assertGreaterThanOrEqual(1, count($responseData));
        // Verify all returned records belong to the same asset
        foreach ($responseData as $item) {
            $this->assertEquals($asset->id, $item['asset_id']);
        }
    }

    #[Test]
    public function test_create_asset_price()
    {
        // Create asset first so it exists in the database
        $asset = Asset::factory()->create();
        $assetPrice = [
            'asset_id' => $asset->id,
            'price' => 123.45,
            'start_dt' => now()->subDays(30)->format('Y-m-d'),
            'end_dt' => '9999-12-31',
        ];

        $this->response = $this->json(
            'POST',
            '/api/asset_prices', $assetPrice
        );

        // Ignore date fields due to format mismatch (API returns ISO format)
        $this->assertApiResponse($assetPrice, ['start_dt', 'end_dt']);
    }

    #[Test]
    public function test_read_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/asset_prices/'.$assetPrice->id
        );

        // Ignore date fields due to format mismatch
        $this->assertApiResponse($assetPrice->toArray(), ['start_dt', 'end_dt']);
    }

    #[Test]
    public function test_read_asset_price_not_found()
    {
        $this->response = $this->json(
            'GET',
            '/api/asset_prices/999999'
        );

        $this->response->assertStatus(404);
    }

    #[Test]
    public function test_update_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();
        // Use the same asset_id to avoid validation error
        $editedAssetPrice = AssetPrice::factory()->make(['asset_id' => $assetPrice->asset_id])->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/asset_prices/'.$assetPrice->id,
            $editedAssetPrice
        );

        // Ignore date fields due to format mismatch
        $this->assertApiResponse($editedAssetPrice, ['start_dt', 'end_dt']);
    }

    #[Test]
    public function test_update_asset_price_not_found()
    {
        $asset = Asset::factory()->create();
        $editedAssetPrice = AssetPrice::factory()->make(['asset_id' => $asset->id])->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/asset_prices/999999',
            $editedAssetPrice
        );

        $this->response->assertStatus(404);
    }

    #[Test]
    public function test_delete_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/asset_prices/'.$assetPrice->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/asset_prices/'.$assetPrice->id
        );

        $this->response->assertStatus(404);
    }

    #[Test]
    public function test_delete_asset_price_not_found()
    {
        $this->response = $this->json(
            'DELETE',
            '/api/asset_prices/999999'
        );

        $this->response->assertStatus(404);
    }
}
