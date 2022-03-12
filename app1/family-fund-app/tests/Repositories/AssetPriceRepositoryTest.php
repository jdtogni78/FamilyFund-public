<?php namespace Tests\Repositories;

use App\Models\AssetPrice;
use App\Repositories\AssetPriceRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class AssetPriceRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var AssetPriceRepository
     */
    protected $assetPriceRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->assetPriceRepo = \App::make(AssetPriceRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_asset_price()
    {
        $assetPrice = AssetPrice::factory()->make()->toArray();

        $createdAssetPrice = $this->assetPriceRepo->create($assetPrice);

        $createdAssetPrice = $createdAssetPrice->toArray();
        $this->assertArrayHasKey('id', $createdAssetPrice);
        $this->assertNotNull($createdAssetPrice['id'], 'Created AssetPrice must have id specified');
        $this->assertNotNull(AssetPrice::find($createdAssetPrice['id']), 'AssetPrice with given id must be in DB');
        $this->assertModelData($assetPrice, $createdAssetPrice);
    }

    /**
     * @test read
     */
    public function test_read_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();

        $dbAssetPrice = $this->assetPriceRepo->find($assetPrice->id);

        $dbAssetPrice = $dbAssetPrice->toArray();
        $this->assertModelData($assetPrice->toArray(), $dbAssetPrice);
    }

    /**
     * @test update
     */
    public function test_update_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();
        $fakeAssetPrice = AssetPrice::factory()->make()->toArray();

        $updatedAssetPrice = $this->assetPriceRepo->update($fakeAssetPrice, $assetPrice->id);

        $this->assertModelData($fakeAssetPrice, $updatedAssetPrice->toArray());
        $dbAssetPrice = $this->assetPriceRepo->find($assetPrice->id);
        $this->assertModelData($fakeAssetPrice, $dbAssetPrice->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();

        $resp = $this->assetPriceRepo->delete($assetPrice->id);

        $this->assertTrue($resp);
        $this->assertNull(AssetPrice::find($assetPrice->id), 'AssetPrice should not exist in DB');
    }
}
