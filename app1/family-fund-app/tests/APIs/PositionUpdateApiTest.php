<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\PositionUpdate;

class PositionUpdateApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_position_update()
    {
        $positionUpdate = PositionUpdate::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/position_updates', $positionUpdate
        );

        $this->assertApiResponse($positionUpdate);
    }

    /**
     * @test
     */
    public function test_read_position_update()
    {
        $positionUpdate = PositionUpdate::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/position_updates/'.$positionUpdate->id
        );

        $this->assertApiResponse($positionUpdate->toArray());
    }

    /**
     * @test
     */
    public function test_update_position_update()
    {
        $positionUpdate = PositionUpdate::factory()->create();
        $editedPositionUpdate = PositionUpdate::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/position_updates/'.$positionUpdate->id,
            $editedPositionUpdate
        );

        $this->assertApiResponse($editedPositionUpdate);
    }

    /**
     * @test
     */
    public function test_delete_position_update()
    {
        $positionUpdate = PositionUpdate::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/position_updates/'.$positionUpdate->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/position_updates/'.$positionUpdate->id
        );

        $this->response->assertStatus(404);
    }
}
