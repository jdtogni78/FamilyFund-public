<?php

namespace Tests\Feature;

use App\Models\AssetChangeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssetChangeLogControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_destroy_deletes_asset_change_log()
    {
        $changeLog = AssetChangeLog::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('assetChangeLogs.destroy', $changeLog->id));

        $response->assertRedirect(route('assetChangeLogs.index'));
        $this->assertDatabaseMissing('asset_change_logs', ['id' => $changeLog->id]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('assetChangeLogs.destroy', 99999));

        $response->assertRedirect(route('assetChangeLogs.index'));
        $response->assertSessionHas('flash_notification');
    }
}
