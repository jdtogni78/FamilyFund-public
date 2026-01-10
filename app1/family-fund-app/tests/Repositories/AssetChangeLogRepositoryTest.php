<?php namespace Tests\Repositories;

use App\Models\AssetChangeLog;
use App\Repositories\AssetChangeLogRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

use PHPUnit\Framework\Attributes\Test;
class AssetChangeLogRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var AssetChangeLogRepository
     */
    protected $assetChangeLogRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->assetChangeLogRepo = \App::make(AssetChangeLogRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->make()->toArray();

        $createdAssetChangeLog = $this->assetChangeLogRepo->create($assetChangeLog);

        $createdAssetChangeLog = $createdAssetChangeLog->toArray();
        $this->assertArrayHasKey('id', $createdAssetChangeLog);
        $this->assertNotNull($createdAssetChangeLog['id'], 'Created AssetChangeLog must have id specified');
        $this->assertNotNull(AssetChangeLog::find($createdAssetChangeLog['id']), 'AssetChangeLog with given id must be in DB');
        $this->assertModelData($assetChangeLog, $createdAssetChangeLog);
    }

    /**
     * @test read
     */
    public function test_read_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->create();

        $dbAssetChangeLog = $this->assetChangeLogRepo->find($assetChangeLog->id);

        $dbAssetChangeLog = $dbAssetChangeLog->toArray();
        $this->assertModelData($assetChangeLog->toArray(), $dbAssetChangeLog);
    }

    /**
     * @test update
     */
    public function test_update_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->create();
        $fakeAssetChangeLog = AssetChangeLog::factory()->make()->toArray();

        $updatedAssetChangeLog = $this->assetChangeLogRepo->update($fakeAssetChangeLog, $assetChangeLog->id);

        $this->assertModelData($fakeAssetChangeLog, $updatedAssetChangeLog->toArray());
        $dbAssetChangeLog = $this->assetChangeLogRepo->find($assetChangeLog->id);
        $this->assertModelData($fakeAssetChangeLog, $dbAssetChangeLog->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->create();

        $resp = $this->assetChangeLogRepo->delete($assetChangeLog->id);

        $this->assertTrue($resp);
        $this->assertNull(AssetChangeLog::find($assetChangeLog->id), 'AssetChangeLog should not exist in DB');
    }
}
