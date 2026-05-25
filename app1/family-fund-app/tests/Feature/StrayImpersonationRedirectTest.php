<?php

namespace Tests\Feature;

use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * RedirectStrayImpersonation middleware + dev-login query passthrough.
 *
 * The ?as= impersonation switch is only honored by /dev-login. The middleware
 * makes a stray ?as= on any other (dev) route route through /dev-login so the
 * impersonation always takes effect instead of silently no-op'ing (the footgun
 * that looks like a cross-tenant leak — you're really still your previous user).
 */
class StrayImpersonationRedirectTest extends TestCase
{
    use DatabaseTransactions;

    private Fund $fund;
    private User $ownerA;
    private User $ownerB;
    private AccountExt $accountA;
    private AccountExt $accountB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fund = Fund::factory()->create();

        $this->ownerA = User::factory()->create();
        $this->ownerB = User::factory()->create();

        $this->accountA = AccountExt::create([
            'fund_id' => $this->fund->id, 'user_id' => $this->ownerA->id,
            'code' => 'A-' . $this->ownerA->id, 'nickname' => 'Acct A', 'type' => 'individual',
        ]);
        $this->accountB = AccountExt::create([
            'fund_id' => $this->fund->id, 'user_id' => $this->ownerB->id,
            'code' => 'B-' . $this->ownerB->id, 'nickname' => 'Acct B', 'type' => 'individual',
        ]);
    }

    public function test_stray_as_on_a_normal_route_redirects_through_dev_login(): void
    {
        $r = $this->get('/accounts?as=acct' . $this->accountA->id);
        $r->assertRedirect('/dev-login/accounts?as=acct' . $this->accountA->id);
    }

    public function test_following_the_redirect_logs_in_as_the_target_owner(): void
    {
        // Land on /dashboard (200 for any authenticated user) rather than a
        // role-gated page — the point is that ?as= switched the auth user.
        $this->followingRedirects()
            ->get('/dashboard?as=acct' . $this->accountA->id)
            ->assertOk();
        $this->assertAuthenticatedAs($this->ownerA);
    }

    public function test_stray_as_preserves_other_query_params_after_login(): void
    {
        // The middleware forwards the whole query string; dev-login consumes the
        // impersonation params and preserves the rest (here ?fund_id=).
        $this->get('/transactions?as=acct' . $this->accountA->id . '&fund_id=' . $this->fund->id)
            ->assertRedirect('/dev-login/transactions?as=acct' . $this->accountA->id . '&fund_id=' . $this->fund->id);

        $this->get('/dev-login/transactions?as=acct' . $this->accountA->id . '&fund_id=' . $this->fund->id)
            ->assertRedirect('/transactions?fund_id=' . $this->fund->id);
        $this->assertAuthenticatedAs($this->ownerA);
    }

    public function test_explicit_as_wins_over_account_id_helper(): void
    {
        // Both present: ?as= (ownerA) must beat ?account_id= (ownerB).
        $this->get('/dev-login/dashboard?as=acct' . $this->accountA->id . '&account_id=' . $this->accountB->id)
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->ownerA);
    }

    public function test_normal_route_without_as_is_not_redirected(): void
    {
        // No ?as= → middleware is a no-op; authed request renders normally.
        $this->actingAs($this->ownerA)->get('/dashboard')->assertOk();
    }

    public function test_dev_login_path_itself_is_not_intercepted(): void
    {
        // Guards against a redirect loop: /dev-login is excluded.
        $this->get('/dev-login/dashboard?as=acct' . $this->accountA->id)
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->ownerA);
    }
}
