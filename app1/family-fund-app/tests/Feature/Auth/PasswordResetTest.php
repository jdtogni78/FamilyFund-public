<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Exercises the self-service password-reset Volt flow: forgot-password emits a
 * ResetPassword notification, the issued token resets the password, and the
 * IP-keyed RateLimiter fires Lockout after the 5-attempt cap.
 */
class PasswordResetTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Stable IP-keyed throttle slate so the limiter doesn't leak between cases.
        RateLimiter::clear('password-forgot|127.0.0.1');
        RateLimiter::clear('password-reset|127.0.0.1');

        // The broker (DatabaseTokenRepository) throttles per-email; clear rows
        // so subsequent sendResetLink calls inside one test class don't hit the
        // 60-second "recently created" cap. delete() is transaction-safe;
        // truncate would implicitly commit.
        DB::table('password_reset_tokens')->delete();

        $this->user = User::factory()->create([
            'password' => Hash::make('original-password'),
        ]);
    }

    public function test_send_reset_link_dispatches_notification_and_inserts_token(): void
    {
        Notification::fake();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $this->user->email)
            ->call('sendPasswordResetLink')
            ->assertHasNoErrors();

        Notification::assertSentTo($this->user, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $this->user->email]);
    }

    public function test_send_reset_link_for_unknown_email_returns_user_error(): void
    {
        Notification::fake();

        Volt::test('pages.auth.forgot-password')
            ->set('email', 'nobody@example.test')
            ->call('sendPasswordResetLink')
            ->assertHasErrors(['email']);

        Notification::assertNothingSent();
    }

    public function test_reset_password_with_valid_token_updates_password_and_logs_user_in(): void
    {
        $token = Password::createToken($this->user);

        Volt::test('pages.auth.reset-password', ['token' => $token])
            ->set('email', $this->user->email)
            ->set('password', 'NewStrongPassword!23')
            ->set('password_confirmation', 'NewStrongPassword!23')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewStrongPassword!23', $this->user->password));
        $this->assertFalse(Hash::check('original-password', $this->user->password));

        $this->assertTrue(Auth::attempt([
            'email' => $this->user->email,
            'password' => 'NewStrongPassword!23',
        ]));
    }

    public function test_reset_password_with_invalid_token_fails_and_password_unchanged(): void
    {
        Volt::test('pages.auth.reset-password', ['token' => 'this-token-is-bogus'])
            ->set('email', $this->user->email)
            ->set('password', 'NewStrongPassword!23')
            ->set('password_confirmation', 'NewStrongPassword!23')
            ->call('resetPassword')
            ->assertHasErrors(['email']);

        $this->user->refresh();
        $this->assertTrue(Hash::check('original-password', $this->user->password));
    }

    public function test_forgot_password_rate_limit_triggers_after_five_attempts(): void
    {
        Event::fake([Lockout::class]);

        // Seed the IP-keyed bucket past the 5-attempt cap so the next call
        // hits the throttle branch deterministically (no real timing).
        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit('password-forgot|127.0.0.1');
        }

        Volt::test('pages.auth.forgot-password')
            ->set('email', $this->user->email)
            ->call('sendPasswordResetLink')
            ->assertHasErrors(['email']);

        Event::assertDispatched(Lockout::class);
    }

    public function test_reset_password_rate_limit_triggers_after_five_attempts(): void
    {
        Event::fake([Lockout::class]);

        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit('password-reset|127.0.0.1');
        }

        Volt::test('pages.auth.reset-password', ['token' => 'doesnt-matter'])
            ->set('email', $this->user->email)
            ->set('password', 'NewStrongPassword!23')
            ->set('password_confirmation', 'NewStrongPassword!23')
            ->call('resetPassword')
            ->assertHasErrors(['email']);

        Event::assertDispatched(Lockout::class);
    }

    public function test_password_reset_tokens_table_exists(): void
    {
        // Guards against the original defect: the broker target table was
        // 'password_reset_tokens' (L11 default) but only legacy 'password_resets'
        // existed, so sendResetLink threw at the SQL layer.
        $this->assertTrue(
            Schema::hasTable('password_reset_tokens'),
            'password_reset_tokens table must exist for the Laravel 11 password broker'
        );
    }
}
