<?php namespace Tests\Repositories;

use App\Models\Portfolio;
use App\Repositories\PortfolioRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class PortfolioRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var PortfolioRepository
     */
    protected $portfolioRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->portfolioRepo = \App::make(PortfolioRepository::class);
    }
    public function test_create_portfolio()
    {
        $portfolio = Portfolio::factory()->make()->toArray();

        $createdPortfolio = $this->portfolioRepo->create($portfolio);

        $createdPortfolio = $createdPortfolio->toArray();
        $this->assertArrayHasKey('id', $createdPortfolio);
        $this->assertNotNull($createdPortfolio['id'], 'Created Portfolio must have id specified');
        $this->assertNotNull(Portfolio::find($createdPortfolio['id']), 'Portfolio with given id must be in DB');
        $this->assertModelData($portfolio, $createdPortfolio);
    }
    public function test_read_portfolio()
    {
        $portfolio = Portfolio::factory()->create();

        $dbPortfolio = $this->portfolioRepo->find($portfolio->id);

        $dbPortfolio = $dbPortfolio->toArray();
        $this->assertModelData($portfolio->toArray(), $dbPortfolio);
    }
    public function test_update_portfolio()
    {
        $portfolio = Portfolio::factory()->create();
        $fakePortfolio = Portfolio::factory()->make()->toArray();

        $updatedPortfolio = $this->portfolioRepo->update($fakePortfolio, $portfolio->id);

        $this->assertModelData($fakePortfolio, $updatedPortfolio->toArray());
        $dbPortfolio = $this->portfolioRepo->find($portfolio->id);
        $this->assertModelData($fakePortfolio, $dbPortfolio->toArray());
    }
    public function test_delete_portfolio()
    {
        $portfolio = Portfolio::factory()->create();

        $resp = $this->portfolioRepo->delete($portfolio->id);

        $this->assertTrue($resp);
        $this->assertNull(Portfolio::find($portfolio->id), 'Portfolio should not exist in DB');
    }
}
