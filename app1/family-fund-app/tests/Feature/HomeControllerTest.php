<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests for HomeController
 * Target: Push from 46.15% to 50%+
 */
class HomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'password' => Hash::make('old-password')
        ]);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_change_password_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('change-password'));

        $response->assertStatus(200);
        $response->assertViewIs('change-password');
    }

    public function test_change_password_requires_authentication()
    {
        $response = $this->get(route('change-password'));

        $response->assertRedirect(route('login'));
    }

    public function test_update_password_successfully_changes_password()
    {
        $response = $this->actingAs($this->user)
            ->post(route('update-password'), [
                'old_password' => 'old-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Password changed successfully!');

        // Verify password was changed
        $this->user->refresh();
        $this->assertTrue(Hash::check('new-password', $this->user->password));
    }

    public function test_update_password_fails_when_old_password_incorrect()
    {
        $response = $this->actingAs($this->user)
            ->post(route('update-password'), [
                'old_password' => 'wrong-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', "Old Password Doesn't match!");

        // Verify password was NOT changed
        $this->user->refresh();
        $this->assertTrue(Hash::check('old-password', $this->user->password));
    }

    public function test_update_password_validates_old_password_required()
    {
        $response = $this->actingAs($this->user)
            ->post(route('update-password'), [
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ]);

        $response->assertSessionHasErrors(['old_password']);
    }

    public function test_update_password_validates_new_password_required()
    {
        $response = $this->actingAs($this->user)
            ->post(route('update-password'), [
                'old_password' => 'old-password',
            ]);

        $response->assertSessionHasErrors(['new_password']);
    }

    public function test_update_password_validates_new_password_confirmation()
    {
        $response = $this->actingAs($this->user)
            ->post(route('update-password'), [
                'old_password' => 'old-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'different-password',
            ]);

        $response->assertSessionHasErrors(['new_password']);
    }
}
