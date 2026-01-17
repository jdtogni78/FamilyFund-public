<?php

namespace Tests\Feature;

use App\Models\AssetChangeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for AssetChangeLogController
 * Target: Push from 15.2% to 50%+
 */
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

    public function test_index_displays_asset_change_logs_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('assetChangeLogs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('asset_change_logs.index');
        $response->assertViewHas('assetChangeLogs');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('assetChangeLogs.create'));

        $response->assertStatus(200);
        $response->assertViewIs('asset_change_logs.create');
    }

    public function test_show_displays_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('assetChangeLogs.show', $assetChangeLog->id));

        $response->assertStatus(200);
        $response->assertViewIs('asset_change_logs.show');
        $response->assertViewHas('assetChangeLog');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('assetChangeLogs.show', 99999));

        $response->assertRedirect(route('assetChangeLogs.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_edit_displays_form_for_existing_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('assetChangeLogs.edit', $assetChangeLog->id));

        $response->assertStatus(200);
        $response->assertViewIs('asset_change_logs.edit');
        $response->assertViewHas('assetChangeLog');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('assetChangeLogs.edit', 99999));

        $response->assertRedirect(route('assetChangeLogs.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_asset_change_log()
    {
        $assetChangeLog = AssetChangeLog::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('assetChangeLogs.destroy', $assetChangeLog->id));

        $response->assertRedirect(route('assetChangeLogs.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('assetChangeLogs.destroy', 99999));

        $response->assertRedirect(route('assetChangeLogs.index'));
        $response->assertSessionHas('flash_notification');
    }
}
