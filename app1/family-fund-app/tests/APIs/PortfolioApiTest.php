<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Portfolio;
use App\Models\Fund;
use App\Http\Resources\PortfolioResource;

class PortfolioApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    public function makePortfolio() {
        $fund = Fund::factory()->create();
        $portfolio = Portfolio::factory()->make(
            ['fund_id' => $fund->id]);
        return $portfolio;
    }

    public function createPortfolio() {
        $fund = Fund::factory()->create();
        $portfolio = Portfolio::factory()->create(
            ['fund_id' => $fund->id]);
        return $portfolio;
    }

    /**
     * @test
     */
    public function test_create_portfolio()
    {
        $portfolio = $this->makePortfolio()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/portfolios', $portfolio
        );

        $this->assertApiResponse($portfolio, ['id']);
    }

    /**
     * @test
     */
    public function test_read_portfolio()
    {
        $portfolio = $this->createPortfolio();

        $this->response = $this->json(
            'GET',
            '/api/portfolios/'.$portfolio->id
        );

        $this->assertApiResponse((new PortfolioResource($portfolio))->toArray(null));
    }

    /**
     * @test
     */
    public function test_update_portfolio()
    {
        $portfolio = $this->createPortfolio();
        $fund = $portfolio->fund()->first();
        $editedPortfolio = Portfolio::factory()->make(
            ['fund_id' => $fund->id])->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/portfolios/'.$portfolio->id,
            $editedPortfolio
        );
        $editedPortfolio['id'] = $portfolio->id;

        $this->assertApiResponse($editedPortfolio);
    }

    /**
     * @test
     */
    public function test_delete_portfolio()
    {
        $portfolio = $this->createPortfolio();

        $this->response = $this->json(
            'DELETE',
             '/api/portfolios/'.$portfolio->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/portfolios/'.$portfolio->id
        );

        $this->response->assertStatus(404);
    }
}
