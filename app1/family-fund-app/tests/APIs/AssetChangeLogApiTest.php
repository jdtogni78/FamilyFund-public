<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\AssetChangeLog;

class AssetChangeLogApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/asset_change_logs', $assetChangeLog
        );

        $this->assertApiResponse($assetChangeLog);
    }

    /**
     * @test
     */
    public function test_read_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/asset_change_logs/'.$assetChangeLog->id
        );

        $this->assertApiResponse($assetChangeLog->toArray());
    }

    /**
     * @test
     */
    public function test_update_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->create();
        $editedAssetChangeLog = AssetChangeLog::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/asset_change_logs/'.$assetChangeLog->id,
            $editedAssetChangeLog
        );

        $this->assertApiResponse($editedAssetChangeLog);
    }

    /**
     * @test
     */
    public function test_delete_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/asset_change_logs/'.$assetChangeLog->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/asset_change_logs/'.$assetChangeLog->id
        );

        $this->response->assertStatus(404);
    }
}
