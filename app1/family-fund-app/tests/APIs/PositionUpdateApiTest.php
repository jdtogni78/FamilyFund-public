<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\PositionUpdate;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for incomplete feature - API routes/controllers not implemented
 */
#[Group('incomplete')]
class PositionUpdateApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_position_update()
    {
        $positionUpdate = PositionUpdate::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/position_updates', $positionUpdate
        );

        $this->assertApiResponse($positionUpdate);
    }

    #[Test]
    public function test_read_position_update()
    {
        $positionUpdate = PositionUpdate::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/position_updates/'.$positionUpdate->id
        );

        $this->assertApiResponse($positionUpdate->toArray());
    }

    #[Test]
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

    #[Test]
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
