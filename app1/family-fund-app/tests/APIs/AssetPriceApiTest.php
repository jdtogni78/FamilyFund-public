<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\AssetPrice;

class AssetPriceApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_asset_price()
    {
        $assetPrice = AssetPrice::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/asset_prices', $assetPrice
        );

        $this->assertApiResponse($assetPrice);
    }

    /**
     * @test
     */
    public function test_read_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/asset_prices/'.$assetPrice->id
        );

        $this->assertApiResponse($assetPrice->toArray());
    }

    /**
     * @test
     */
    public function test_update_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();
        $editedAssetPrice = AssetPrice::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/asset_prices/'.$assetPrice->id,
            $editedAssetPrice
        );

        $this->assertApiResponse($editedAssetPrice);
    }

    /**
     * @test
     */
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
