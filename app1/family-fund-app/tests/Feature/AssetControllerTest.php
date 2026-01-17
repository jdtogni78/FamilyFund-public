<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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

    public function test_destroy_deletes_asset()
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('assets.destroy', $asset->id));

        $response->assertRedirect(route('assets.index'));
        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('assets.destroy', 99999));

        $response->assertRedirect(route('assets.index'));
        $response->assertSessionHas('flash_notification');
    }
}
