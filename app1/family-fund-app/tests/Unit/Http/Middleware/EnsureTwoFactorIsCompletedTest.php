<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnsureTwoFactorIsCompleted;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class EnsureTwoFactorIsCompletedTest extends TestCase
{
    use DatabaseTransactions;

    private function request(string $path = '/dashboard', ?User $user = null): Request
    {
        $request = Request::create($path);
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    /**
     * Returns a [$next, $state] tuple. $state is an object whose ->called
     * property is set to true if $next is invoked. (A returned-by-reference
     * scalar would be detached by the time the closure is invoked outside
     * this helper.)
     */
    private function next(): array
    {
        $state = new \stdClass();
        $state->called = false;
        $next = function ($r) use ($state) {
            $state->called = true;
            return new Response('ok');
        };
        return [$next, $state];
    }

    public function test_passes_through_when_user_is_not_authenticated(): void
    {
        [$next, $s] = $this->next();

        $response = (new EnsureTwoFactorIsCompleted())
            ->handle($this->request(), $next);

        $this->assertTrue($s->called);
        $this->assertSame('ok', $response->getContent());
    }

    public function test_passes_through_when_no_pending_2fa_session(): void
    {
        $user = User::factory()->create();
        [$next, $s] = $this->next();

        $response = (new EnsureTwoFactorIsCompleted())
            ->handle($this->request('/dashboard', $user), $next);

        $this->assertTrue($s->called);
        $this->assertSame('ok', $response->getContent());
    }

    public function test_passes_through_when_login_session_id_doesnt_match_user(): void
    {
        $user = User::factory()->create([
            'two_factor_secret'       => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
        ]);
        session()->put('login.id', $user->id + 999);
        [$next, $s] = $this->next();

        $response = (new EnsureTwoFactorIsCompleted())
            ->handle($this->request('/dashboard', $user), $next);

        $this->assertTrue($s->called);
        $this->assertSame('ok', $response->getContent());
    }

    public function test_redirects_to_challenge_when_2fa_pending_and_enabled(): void
    {
        $user = User::factory()->create([
            'two_factor_secret'       => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
        ]);
        session()->put('login.id', $user->id);
        [$next, $s] = $this->next();

        $response = (new EnsureTwoFactorIsCompleted())
            ->handle($this->request('/dashboard', $user), $next);

        $this->assertFalse($s->called);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('two-factor-challenge', $response->getTargetUrl());
    }

    public function test_passes_through_on_whitelisted_route_even_with_pending_2fa(): void
    {
        $user = User::factory()->create([
            'two_factor_secret'       => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
        ]);
        session()->put('login.id', $user->id);
        [$next, $s] = $this->next();

        $response = (new EnsureTwoFactorIsCompleted())
            ->handle($this->request('/two-factor-challenge', $user), $next);

        $this->assertTrue($s->called);
        $this->assertSame('ok', $response->getContent());
    }

    public function test_passes_through_when_2fa_not_enabled_for_pending_session(): void
    {
        // Pending session is set but user never enabled 2FA — middleware
        // should let the request through (the login flow that set
        // login.id is the layer that would have errored).
        $user = User::factory()->create([
            'two_factor_secret'       => null,
            'two_factor_confirmed_at' => null,
        ]);
        session()->put('login.id', $user->id);
        [$next, $s] = $this->next();

        $response = (new EnsureTwoFactorIsCompleted())
            ->handle($this->request('/dashboard', $user), $next);

        $this->assertTrue($s->called);
        $this->assertSame('ok', $response->getContent());
    }
}
