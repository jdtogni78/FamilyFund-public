<?php

namespace Tests\Feature;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Tests for Two-Factor Authentication functionality.
 */
class TwoFactorAuthTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a simple user without DataFactory dependencies
        $this->user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);
        $this->google2fa = new Google2FA();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== 2FA Setup Page Tests ====================

    public function test_two_factor_setup_page_renders()
    {
        $response = $this->actingAs($this->user)->get('/two-factor');
        $response->assertStatus(200);
        $response->assertSee('Set Up Two-Factor Authentication');
    }

    public function test_two_factor_setup_generates_secret()
    {
        $this->assertNull($this->user->two_factor_secret);

        $this->actingAs($this->user)->get('/two-factor');

        $this->user->refresh();
        $this->assertNotNull($this->user->two_factor_secret);
    }

    public function test_two_factor_enable_with_valid_code()
    {
        // First visit setup to generate secret
        $this->actingAs($this->user)->get('/two-factor');
        $this->user->refresh();

        // Generate valid code
        $validCode = $this->google2fa->getCurrentOtp($this->user->two_factor_secret);

        $response = $this->actingAs($this->user)->post('/two-factor', [
            'code' => $validCode,
        ]);

        $response->assertRedirect('/two-factor/recovery-codes');

        $this->user->refresh();
        $this->assertNotNull($this->user->two_factor_confirmed_at);
        $this->assertNotNull($this->user->two_factor_recovery_codes);
        $this->assertCount(8, $this->user->two_factor_recovery_codes);
    }

    public function test_two_factor_enable_with_invalid_code_fails()
    {
        // First visit setup to generate secret
        $this->actingAs($this->user)->get('/two-factor');

        $response = $this->actingAs($this->user)->post('/two-factor', [
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');

        $this->user->refresh();
        $this->assertNull($this->user->two_factor_confirmed_at);
    }

    // ==================== 2FA Challenge Page Tests ====================

    public function test_two_factor_challenge_page_without_session_redirects_to_login()
    {
        $response = $this->get('/two-factor-challenge');
        $response->assertRedirect('/login');
    }

    public function test_two_factor_challenge_page_with_session_renders()
    {
        // Setup 2FA for user
        $this->enableTwoFactorForUser($this->user);

        // Simulate session state after password verification
        session(['two_factor_user_id' => $this->user->id]);

        $response = $this->get('/two-factor-challenge');
        $response->assertStatus(200);
        $response->assertSee('Two-Factor Authentication');
    }

    public function test_two_factor_verify_with_valid_code()
    {
        $this->enableTwoFactorForUser($this->user);

        session(['two_factor_user_id' => $this->user->id]);

        $validCode = $this->google2fa->getCurrentOtp($this->user->two_factor_secret);

        $response = $this->post('/two-factor-challenge', [
            'code' => $validCode,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_two_factor_verify_with_invalid_code_fails()
    {
        $this->enableTwoFactorForUser($this->user);

        session(['two_factor_user_id' => $this->user->id]);

        $response = $this->post('/two-factor-challenge', [
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_two_factor_verify_with_recovery_code()
    {
        $this->enableTwoFactorForUser($this->user);

        $recoveryCodes = $this->user->two_factor_recovery_codes;
        $recoveryCode = $recoveryCodes[0];

        session(['two_factor_user_id' => $this->user->id]);

        $response = $this->post('/two-factor-challenge', [
            'code' => $recoveryCode,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);

        // Verify recovery code was consumed
        $this->user->refresh();
        $this->assertNotContains($recoveryCode, $this->user->two_factor_recovery_codes);
        $this->assertCount(7, $this->user->two_factor_recovery_codes);
    }

    // ==================== 2FA Disable Tests ====================

    public function test_two_factor_disable_requires_password()
    {
        $this->enableTwoFactorForUser($this->user);

        $response = $this->actingAs($this->user)->delete('/two-factor', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');

        $this->user->refresh();
        $this->assertTrue($this->user->hasTwoFactorEnabled());
    }

    public function test_two_factor_disable_with_correct_password()
    {
        $this->enableTwoFactorForUser($this->user);

        $response = $this->actingAs($this->user)->delete('/two-factor', [
            'password' => 'password', // Default factory password
        ]);

        $response->assertRedirect('/profile');

        $this->user->refresh();
        $this->assertFalse($this->user->hasTwoFactorEnabled());
        $this->assertNull($this->user->two_factor_secret);
        $this->assertNull($this->user->two_factor_recovery_codes);
    }

    // ==================== Recovery Codes Tests ====================

    public function test_recovery_codes_page_renders_when_2fa_enabled()
    {
        $this->enableTwoFactorForUser($this->user);

        $response = $this->actingAs($this->user)->get('/two-factor/recovery-codes');
        $response->assertStatus(200);
        $response->assertSee('Save Your Recovery Codes');
    }

    public function test_recovery_codes_page_redirects_when_2fa_not_enabled()
    {
        $response = $this->actingAs($this->user)->get('/two-factor/recovery-codes');
        $response->assertRedirect('/two-factor');
    }

    public function test_regenerate_recovery_codes()
    {
        $this->enableTwoFactorForUser($this->user);

        $originalCodes = $this->user->two_factor_recovery_codes;

        $response = $this->actingAs($this->user)->post('/two-factor/recovery-codes');

        $response->assertRedirect('/two-factor/recovery-codes');

        $this->user->refresh();
        $this->assertNotEquals($originalCodes, $this->user->two_factor_recovery_codes);
        $this->assertCount(8, $this->user->two_factor_recovery_codes);
    }

    // ==================== Login Activity Tests ====================

    /**
     * @group needs-livewire-setup
     */
    public function test_successful_login_records_activity()
    {
        // This test requires Livewire component testing setup for login form
        // Skip for now - login activity recording is tested manually
        $this->markTestSkipped('Requires Livewire component testing setup');
    }

    public function test_login_with_wrong_password_does_not_authenticate()
    {
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        // User should not be authenticated
        $this->assertGuest();
    }

    // ==================== Profile 2FA Section Tests ====================

    public function test_profile_shows_2fa_disabled_status()
    {
        $response = $this->actingAs($this->user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Two-factor authentication is not enabled');
        $response->assertSee('Enable 2FA');
    }

    public function test_profile_shows_2fa_enabled_status()
    {
        $this->enableTwoFactorForUser($this->user);

        $response = $this->actingAs($this->user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Two-factor authentication is enabled');
        $response->assertSee('Manage 2FA');
    }

    // ==================== Helper Methods ====================

    private function enableTwoFactorForUser(User $user): void
    {
        $secret = $this->google2fa->generateSecretKey();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = [
            'CODE1-CODE1',
            'CODE2-CODE2',
            'CODE3-CODE3',
            'CODE4-CODE4',
            'CODE5-CODE5',
            'CODE6-CODE6',
            'CODE7-CODE7',
            'CODE8-CODE8',
        ];
        $user->save();
    }
}
