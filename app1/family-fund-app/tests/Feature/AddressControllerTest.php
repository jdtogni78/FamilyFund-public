<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for AddressController
 * Target: Push from 15.2% to 50%+
 */
class AddressControllerTest extends TestCase
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

    public function test_index_displays_addresses_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('addresses.index'));

        $response->assertStatus(200);
        $response->assertViewIs('addresses.index');
        $response->assertViewHas('addresses');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('addresses.create'));

        $response->assertStatus(200);
        $response->assertViewIs('addresses.create');
    }

    public function test_show_displays_address()
    {
        $address = Address::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('addresses.show', $address->id));

        $response->assertStatus(200);
        $response->assertViewIs('addresses.show');
        $response->assertViewHas('address');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('addresses.show', 99999));

        $response->assertRedirect(route('addresses.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_edit_displays_form_for_existing_address()
    {
        $address = Address::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('addresses.edit', $address->id));

        $response->assertStatus(200);
        $response->assertViewIs('addresses.edit');
        $response->assertViewHas('address');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('addresses.edit', 99999));

        $response->assertRedirect(route('addresses.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_address()
    {
        $address = Address::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('addresses.destroy', $address->id));

        $response->assertRedirect(route('addresses.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('addresses.destroy', 99999));

        $response->assertRedirect(route('addresses.index'));
        $response->assertSessionHas('flash_notification');
    }
}
