<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for UserController
 * Target: Push from 32.4% to 50%+
 */
class UserControllerTest extends TestCase
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

    public function test_index_displays_users_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('users.index');
        $response->assertViewHas('users');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('users.create'));

        $response->assertStatus(200);
        $response->assertViewIs('users.create');
    }

    public function test_show_displays_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('users.show', $user->id));

        $response->assertStatus(200);
        $response->assertViewIs('users.show');
        $response->assertViewHas('user');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('users.show', 99999));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_edit_displays_form_for_existing_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('users.edit', $user->id));

        $response->assertStatus(200);
        $response->assertViewIs('users.edit');
        $response->assertViewHas('user');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('users.edit', 99999));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('users.destroy', $user->id));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('users.destroy', 99999));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('flash_notification');
    }
}
