<?php namespace Tests\Repositories;

use App\Models\PortfolioAsset;
use App\Repositories\PortfolioAssetRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class PortfolioAssetRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var PortfolioAssetRepository
     */
    protected $portfolioAssetRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->portfolioAssetRepo = \App::make(PortfolioAssetRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_portfolio_asset()
    {
        $portfolioAsset = PortfolioAsset::factory()->make()->toArray();

        $createdPortfolioAsset = $this->portfolioAssetRepo->create($portfolioAsset);

        $createdPortfolioAsset = $createdPortfolioAsset->toArray();
        $this->assertArrayHasKey('id', $createdPortfolioAsset);
        $this->assertNotNull($createdPortfolioAsset['id'], 'Created PortfolioAsset must have id specified');
        $this->assertNotNull(PortfolioAsset::find($createdPortfolioAsset['id']), 'PortfolioAsset with given id must be in DB');
        $this->assertModelData($portfolioAsset, $createdPortfolioAsset);
    }

    /**
     * @test read
     */
    public function test_read_portfolio_asset()
    {
        $portfolioAsset = PortfolioAsset::factory()->create();

        $dbPortfolioAsset = $this->portfolioAssetRepo->find($portfolioAsset->id);

        $dbPortfolioAsset = $dbPortfolioAsset->toArray();
        $this->assertModelData($portfolioAsset->toArray(), $dbPortfolioAsset);
    }

    /**
     * @test update
     */
    public function test_update_portfolio_asset()
    {
        $portfolioAsset = PortfolioAsset::factory()->create();
        $fakePortfolioAsset = PortfolioAsset::factory()->make()->toArray();

        $updatedPortfolioAsset = $this->portfolioAssetRepo->update($fakePortfolioAsset, $portfolioAsset->id);

        $this->assertModelData($fakePortfolioAsset, $updatedPortfolioAsset->toArray());
        $dbPortfolioAsset = $this->portfolioAssetRepo->find($portfolioAsset->id);
        $this->assertModelData($fakePortfolioAsset, $dbPortfolioAsset->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_portfolio_asset()
    {
        $portfolioAsset = PortfolioAsset::factory()->create();

        $resp = $this->portfolioAssetRepo->delete($portfolioAsset->id);

        $this->assertTrue($resp);
        $this->assertNull(PortfolioAsset::find($portfolioAsset->id), 'PortfolioAsset should not exist in DB');
    }
}
