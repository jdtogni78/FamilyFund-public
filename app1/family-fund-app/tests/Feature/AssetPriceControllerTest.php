<?php

namespace Tests\Feature;

use App\Models\AssetPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for AssetPriceController
 * Target: Push from 20.9% to 50%+
 */
class AssetPriceControllerTest extends TestCase
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

    public function test_index_displays_asset_prices_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index'));

        $response->assertStatus(200);
        $response->assertViewIs('asset_prices.index');
        $response->assertViewHas('assetPrices');
    }

    public function test_index_filters_by_name()
    {
        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['name' => 'Test']));

        $response->assertStatus(200);
        $response->assertViewIs('asset_prices.index');
        $response->assertViewHas('assetPrices');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.create'));

        $response->assertStatus(200);
        $response->assertViewIs('asset_prices.create');
    }

    public function test_show_displays_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.show', $assetPrice->id));

        $response->assertStatus(200);
        $response->assertViewIs('asset_prices.show');
        $response->assertViewHas('assetPrice');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.show', 99999));

        $response->assertRedirect(route('assetPrices.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_edit_displays_form_for_existing_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.edit', $assetPrice->id));

        $response->assertStatus(200);
        $response->assertViewIs('asset_prices.edit');
        $response->assertViewHas('assetPrice');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.edit', 99999));

        $response->assertRedirect(route('assetPrices.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_asset_price()
    {
        $assetPrice = AssetPrice::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('assetPrices.destroy', $assetPrice->id));

        $response->assertRedirect(route('assetPrices.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('assetPrices.destroy', 99999));

        $response->assertRedirect(route('assetPrices.index'));
        $response->assertSessionHas('flash_notification');
    }
}
