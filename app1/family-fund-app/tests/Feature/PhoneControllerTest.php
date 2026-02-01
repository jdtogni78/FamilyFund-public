<?php

namespace Tests\Feature;

use App\Models\Phone;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for PhoneController
 * Target: Push from 15.2% to 50%+
 */
class PhoneControllerTest extends TestCase
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

    public function test_index_displays_phones_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('phones.index'));

        $response->assertStatus(200);
        $response->assertViewIs('phones.index');
        $response->assertViewHas('phones');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('phones.create'));

        $response->assertStatus(200);
        $response->assertViewIs('phones.create');
    }

    public function test_show_displays_phone()
    {
        $phone = Phone::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('phones.show', $phone->id));

        $response->assertStatus(200);
        $response->assertViewIs('phones.show');
        $response->assertViewHas('phone');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('phones.show', 99999));

        $response->assertRedirect(route('phones.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_edit_displays_form_for_existing_phone()
    {
        $phone = Phone::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('phones.edit', $phone->id));

        $response->assertStatus(200);
        $response->assertViewIs('phones.edit');
        $response->assertViewHas('phone');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('phones.edit', 99999));

        $response->assertRedirect(route('phones.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_phone()
    {
        $phone = Phone::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('phones.destroy', $phone->id));

        $response->assertRedirect(route('phones.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('phones.destroy', 99999));

        $response->assertRedirect(route('phones.index'));
        $response->assertSessionHas('flash_notification');
    }
}
