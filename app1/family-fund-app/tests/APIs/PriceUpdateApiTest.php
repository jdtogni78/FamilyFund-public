<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\PriceUpdate;

class PriceUpdateApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_price_update()
    {
        $priceUpdate = PriceUpdate::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/price_updates', $priceUpdate
        );

        $this->assertApiResponse($priceUpdate);
    }

    /**
     * @test
     */
    public function test_read_price_update()
    {
        $priceUpdate = PriceUpdate::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/price_updates/'.$priceUpdate->id
        );

        $this->assertApiResponse($priceUpdate->toArray());
    }

    /**
     * @test
     */
    public function test_update_price_update()
    {
        $priceUpdate = PriceUpdate::factory()->create();
        $editedPriceUpdate = PriceUpdate::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/price_updates/'.$priceUpdate->id,
            $editedPriceUpdate
        );

        $this->assertApiResponse($editedPriceUpdate);
    }

    /**
     * @test
     */
    public function test_delete_price_update()
    {
        $priceUpdate = PriceUpdate::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/price_updates/'.$priceUpdate->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/price_updates/'.$priceUpdate->id
        );

        $this->response->assertStatus(404);
    }
}
