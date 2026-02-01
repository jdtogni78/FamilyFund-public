<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\TradePortfolio;

use PHPUnit\Framework\Attributes\Test;
class TradePortfolioApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/trade_portfolios', $tradePortfolio
        );

        // Ignore date fields and items (API returns 'Y-m-d' format vs model's ISO format)
        $this->assertApiResponse($tradePortfolio, ['start_dt', 'end_dt', 'items']);
    }

    #[Test]
    public function test_read_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/trade_portfolios/'.$tradePortfolio->id
        );

        // Ignore date fields and items (API returns 'Y-m-d' format vs model's ISO format)
        $this->assertApiResponse($tradePortfolio->toArray(), ['start_dt', 'end_dt', 'items']);
    }

    #[Test]
    public function test_update_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->create();
        $editedTradePortfolio = TradePortfolio::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/trade_portfolios/'.$tradePortfolio->id,
            $editedTradePortfolio
        );

        // Ignore date fields and items (API returns 'Y-m-d' format vs model's ISO format)
        $this->assertApiResponse($editedTradePortfolio, ['start_dt', 'end_dt', 'items']);
    }

    #[Test]
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

    #[Test]
    public function test_trade_portfolio_returns_fund_name_as_nickname()
    {
        // Create a trade portfolio (which auto-creates portfolio -> fund)
        $tradePortfolio = TradePortfolio::factory()->create();

        // Get the fund name through the relationship chain
        $fundName = $tradePortfolio->portfolio->fund->name;

        $this->response = $this->json(
            'GET',
            '/api/trade_portfolios/'.$tradePortfolio->id
        );

        $this->response->assertStatus(200);

        // Verify the nickname field contains the fund name
        $responseData = $this->response->json('data');
        $this->assertEquals($fundName, $responseData['nickname'],
            'nickname should be populated from the fund name');
    }

    #[Test]
    public function test_trade_portfolio_nickname_null_when_no_portfolio()
    {
        // Create a trade portfolio without a portfolio relationship
        $tradePortfolio = TradePortfolio::factory()->create([
            'portfolio_id' => null
        ]);

        $this->response = $this->json(
            'GET',
            '/api/trade_portfolios/'.$tradePortfolio->id
        );

        $this->response->assertStatus(200);

        // Verify the nickname field is null when no portfolio relationship
        $responseData = $this->response->json('data');
        $this->assertNull($responseData['nickname'],
            'nickname should be null when portfolio relationship is missing');
    }
}
