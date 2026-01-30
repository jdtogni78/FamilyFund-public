<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Portfolio;
use App\Models\Fund;
use App\Http\Resources\PortfolioResource;

use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function test_create_portfolio()
    {
        $portfolio = $this->makePortfolio()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/portfolios', $portfolio
        );

        $this->assertApiResponse($portfolio, ['id']);
    }

    #[Test]
    public function test_read_portfolio()
    {
        $portfolio = $this->createPortfolio();

        $this->response = $this->json(
            'GET',
            '/api/portfolios/'.$portfolio->id
        );

        $this->assertApiResponse((new PortfolioResource($portfolio))->toArray(null));
    }

    #[Test]
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

    #[Test]
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

    // =========================================================================
    // Type and Category Tests
    // =========================================================================

    #[Test]
    public function test_portfolio_response_includes_type_and_category()
    {
        $fund = Fund::factory()->create();
        $portfolio = Portfolio::factory()->create([
            'fund_id' => $fund->id,
            'type' => 'brokerage',
            'category' => 'taxable',
        ]);

        $this->response = $this->json('GET', '/api/portfolios/' . $portfolio->id);

        $this->response->assertStatus(200);
        $this->response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'fund_id',
                'source',
                'type',
                'category',
            ]
        ]);
        $this->response->assertJsonPath('data.type', 'brokerage');
        $this->response->assertJsonPath('data.category', 'taxable');
    }

    #[Test]
    public function test_update_portfolio_type_and_category()
    {
        $portfolio = $this->createPortfolio();
        $fund = $portfolio->fund;

        // PUT requires all required fields
        $this->response = $this->json(
            'PUT',
            '/api/portfolios/' . $portfolio->id,
            [
                'fund_id' => $fund->id,
                'source' => $portfolio->source,
                'type' => '401k',
                'category' => 'retirement',
            ]
        );

        $this->assertApiSuccess();
        $this->response->assertJsonPath('data.type', '401k');
        $this->response->assertJsonPath('data.category', 'retirement');

        // Verify persisted
        $updated = Portfolio::find($portfolio->id);
        $this->assertEquals('401k', $updated->type);
        $this->assertEquals('retirement', $updated->category);
    }

    #[Test]
    public function test_list_portfolios_includes_type_and_category()
    {
        $fund = Fund::factory()->create();
        Portfolio::factory()->create([
            'fund_id' => $fund->id,
            'type' => 'real_estate',
            'category' => 'taxable',
        ]);

        $this->response = $this->json('GET', '/api/portfolios');

        $this->response->assertStatus(200);
        // Check that at least one portfolio in the list has type/category
        $portfolios = $this->response->json('data');
        $found = false;
        foreach ($portfolios as $p) {
            if (isset($p['type']) && $p['type'] === 'real_estate') {
                $found = true;
                $this->assertEquals('taxable', $p['category']);
                break;
            }
        }
        $this->assertTrue($found, 'Portfolio with type real_estate not found in list');
    }

    #[Test]
    public function test_create_portfolio_with_type_and_category()
    {
        $fund = Fund::factory()->create();
        $portfolioData = [
            'fund_id' => $fund->id,
            'source' => 'TEST_' . uniqid(),
            'type' => 'mortgage',
            'category' => 'liability',
        ];

        $this->response = $this->json('POST', '/api/portfolios', $portfolioData);

        $this->assertApiSuccess();
        $this->response->assertJsonPath('data.type', 'mortgage');
        $this->response->assertJsonPath('data.category', 'liability');
    }
}
