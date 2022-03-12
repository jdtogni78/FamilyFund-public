<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\PortfolioAsset;

class PortfolioAssetApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_portfolio_asset()
    {
        $portfolioAsset = PortfolioAsset::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/portfolio_assets', $portfolioAsset
        );

        $this->assertApiResponse($portfolioAsset);
    }

    /**
     * @test
     */
    public function test_read_portfolio_asset()
    {
        $portfolioAsset = PortfolioAsset::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/portfolio_assets/'.$portfolioAsset->id
        );

        $this->assertApiResponse($portfolioAsset->toArray());
    }

    /**
     * @test
     */
    public function test_update_portfolio_asset()
    {
        $portfolioAsset = PortfolioAsset::factory()->create();
        $editedPortfolioAsset = PortfolioAsset::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/portfolio_assets/'.$portfolioAsset->id,
            $editedPortfolioAsset
        );

        $this->assertApiResponse($editedPortfolioAsset);
    }

    /**
     * @test
     */
    public function test_delete_portfolio_asset()
    {
        $portfolioAsset = PortfolioAsset::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/portfolio_assets/'.$portfolioAsset->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/portfolio_assets/'.$portfolioAsset->id
        );

        $this->response->assertStatus(404);
    }
}
