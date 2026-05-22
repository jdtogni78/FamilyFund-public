<?php

namespace Tests\Unit\Livewire\Forms;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Volt;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Covers the LoginForm branches the existing TwoFactorAuthTest doesn't
 * exercise: failed auth path (RateLimiter::hit + recordFailed +
 * ValidationException), 2FA-pending path (Session puts + Auth::logout
 * + requiresTwoFactor flag), and the rate-limit-exhausted path
 * (Lockout event + seconds-based throttle message).
 */
class LoginFormTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);
        // Each test starts from a clean rate-limit slate so calls don't
        // leak between cases.
        RateLimiter::clear(strtolower($this->user->email) . '|127.0.0.1');
    }

    public function test_failed_login_records_activity_and_hits_rate_limiter(): void
    {
        Volt::test('pages.auth.login')
            ->set('form.email', $this->user->email)
            ->set('form.password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['form.email']);

        $this->assertDatabaseHas('login_activities', [
            'user_id' => null,
            'status'  => LoginActivity::STATUS_FAILED,
        ]);
        $this->assertGuest();
    }

    public function test_2fa_enabled_user_login_sets_pending_flag_and_logs_out(): void
    {
        $google2fa = new Google2FA();
        $this->user->update([
            'two_factor_secret'       => encrypt($google2fa->generateSecretKey()),
            'two_factor_confirmed_at' => now(),
        ]);

        Volt::test('pages.auth.login')
            ->set('form.email', $this->user->email)
            ->set('form.password', 'correct-password')
            ->set('form.remember', true)
            ->call('login')
            ->assertHasNoErrors()
            ->assertSet('form.requiresTwoFactor', true);

        // Session puts for the 2FA challenge.
        $this->assertSame($this->user->id, Session::get('two_factor_user_id'));
        $this->assertTrue(Session::get('two_factor_remember'));

        // User is logged OUT until they complete the 2FA challenge.
        $this->assertGuest();

        $this->assertDatabaseHas('login_activities', [
            'user_id' => $this->user->id,
            'status'  => LoginActivity::STATUS_TWO_FACTOR_PENDING,
        ]);
    }

    public function test_rate_limit_exceeded_throws_throttle_error_and_fires_lockout(): void
    {
        $key = strtolower($this->user->email) . '|127.0.0.1';
        // Seed the limiter past the 5-attempt cap.
        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit($key);
        }

        Event::fake([Lockout::class]);

        Volt::test('pages.auth.login')
            ->set('form.email', $this->user->email)
            ->set('form.password', 'irrelevant')
            ->call('login')
            ->assertHasErrors(['form.email']);

        Event::assertDispatched(Lockout::class);
    }
}
