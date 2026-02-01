<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for AssetController
 * Target: Push from 48.5% to 50%+
 */
class AssetControllerTest extends TestCase
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

    public function test_show_displays_asset()
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('assets.show', $asset->id));

        $response->assertStatus(200);
        $response->assertViewIs('assets.show');
        $response->assertViewHas('asset');
    }

    public function test_destroy_handles_asset()
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('assets.destroy', $asset->id));

        $response->assertRedirect(route('assets.index'));
        $response->assertSessionHas('flash_notification');
    }
}
