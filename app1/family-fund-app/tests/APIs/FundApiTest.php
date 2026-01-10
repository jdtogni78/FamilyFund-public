<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use Tests\DataFactory;
use App\Models\Fund;
use App\Http\Resources\FundResource;

use PHPUnit\Framework\Attributes\Test;
class FundApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_fund()
    {
        $fund = Fund::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/funds', $fund
        );

        $this->assertApiResponse($fund, ['id']);
    }

    #[Test]
    public function test_read_fund()
    {
        $fund = (new DataFactory())->createFund();

        $this->response = $this->json(
            'GET',
            '/api/funds/'.$fund->id
        );

        $this->assertApiResponse((new FundResource($fund))->toArray(null));
    }

    #[Test]
    public function test_update_fund()
    {
        $fund = Fund::factory()->create();
        $editedFund = Fund::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/funds/'.$fund->id,
            $editedFund
        );

        $this->assertApiResponse($editedFund, ['id']);
    }

    #[Test]
    public function test_delete_fund()
    {
        $fund = Fund::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/funds/'.$fund->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/funds/'.$fund->id
        );

        $this->response->assertStatus(404);
    }
}
