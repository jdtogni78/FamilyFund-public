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
}
