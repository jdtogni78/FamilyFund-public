<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\TradePortfolio;

class TradePortfolioApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/trade_portfolios', $tradePortfolio
        );

        $this->assertApiResponse($tradePortfolio);
    }

    /**
     * @test
     */
    public function test_read_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/trade_portfolios/'.$tradePortfolio->id
        );

        $this->assertApiResponse($tradePortfolio->toArray());
    }

    /**
     * @test
     */
    public function test_update_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->create();
        $editedTradePortfolio = TradePortfolio::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/trade_portfolios/'.$tradePortfolio->id,
            $editedTradePortfolio
        );

        $this->assertApiResponse($editedTradePortfolio);
    }

    /**
     * @test
     */
    public function test_delete_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/trade_portfolios/'.$tradePortfolio->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/trade_portfolios/'.$tradePortfolio->id
        );

        $this->response->assertStatus(404);
    }
}
