<?php

namespace Tests\Feature;

use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Fixtures\TestFixtures;
use Tests\TestCase;

/**
 * HTTP-level cross-tenant isolation.
 *
 * AuthorizationTest already covers the policy/service layer. This file
 * exercises the actual rendered pages a user could browse to, asserting
 * that one tenant cannot pull another tenant's data — and, crucially,
 * that even if a page returns 200 it does not leak the foreign tenant's
 * identifying fields.
 *
 * Tenants:
 *   - Fund X with beneficiary X (owns account X) + a sibling account in
 *     the same fund owned by someone else.
 *   - Fund Y with beneficiary Y (owns account Y).
 *
 * "Blocked" = 403 OR a redirect away from the resource (the controllers
 * either throw via the policy or null-out the scoped query and redirect
 * to the index). Either is acceptable; a 200 that renders the foreign
 * record is not.
 */
class CrossTenantIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private Fund $fundX;
    private Fund $fundY;

    private User $beneficiaryX;
    private User $beneficiaryY;
    private User $unassigned;
    private User $systemAdmin;

    private AccountExt $accountX;       // owned by beneficiaryX, in fundX
    private AccountExt $accountY;       // owned by beneficiaryY, in fundY
    private AccountExt $siblingAccountX; // in fundX, owned by a different user

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->fundX = Fund::factory()->create();
        $this->fundY = Fund::factory()->create();

        $x = TestFixtures::aclUsers($this->fundX);
        $y = TestFixtures::aclUsers($this->fundY);

        $this->beneficiaryX = $x['beneficiary'];
        $this->accountX     = $x['beneficiaryAccount'];
        $this->systemAdmin  = $x['systemAdmin'];
        $this->unassigned   = $x['unassigned'];

        $this->beneficiaryY = $y['beneficiary'];
        $this->accountY     = $y['beneficiaryAccount'];

        // A second account in fund X owned by a *different* user, to test
        // same-fund sibling isolation (beneficiaries can't see each other).
        $sibling = User::factory()->create();
        $this->siblingAccountX = AccountExt::create([
            'fund_id'  => $this->fundX->id,
            'user_id'  => $sibling->id,
            'code'     => 'SIB-' . $sibling->id,
            'nickname' => 'Sibling Account FundX',
            'type'     => 'individual',
        ]);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    private function assertBlocked($response, string $leakNeedle, string $ctx): void
    {
        $status = $response->status();
        $blocked = $status === 403 || $response->isRedirect();
        $this->assertTrue(
            $blocked,
            "$ctx: expected 403 or redirect, got $status"
        );
        // Defense in depth: even on an unexpected 200, the foreign
        // record's identifying string must not appear in the body.
        if ($status === 200) {
            $this->assertStringNotContainsString(
                $leakNeedle,
                $response->getContent(),
                "$ctx: foreign data leaked into a 200 response"
            );
        }
    }

    // ==================== Accounts page ====================

    public function test_beneficiary_can_view_own_account(): void
    {
        $r = $this->actingAs($this->beneficiaryX)->get('/accounts/' . $this->accountX->id);
        $r->assertOk();
    }

    public function test_beneficiary_cannot_view_account_in_another_fund(): void
    {
        $r = $this->actingAs($this->beneficiaryX)->get('/accounts/' . $this->accountY->id);
        $this->assertBlocked($r, $this->accountY->code, 'beneficiaryX -> accountY (cross-fund)');
    }

    public function test_beneficiary_cannot_view_sibling_account_same_fund(): void
    {
        $r = $this->actingAs($this->beneficiaryX)->get('/accounts/' . $this->siblingAccountX->id);
        $this->assertBlocked($r, $this->siblingAccountX->code, 'beneficiaryX -> sibling account (same fund)');
    }

    public function test_unassigned_user_cannot_view_any_account(): void
    {
        $r = $this->actingAs($this->unassigned)->get('/accounts/' . $this->accountX->id);
        $this->assertBlocked($r, $this->accountX->code, 'unassigned -> accountX');
    }

    public function test_anonymous_cannot_view_account(): void
    {
        $r = $this->get('/accounts/' . $this->accountX->id);
        $this->assertTrue($r->isRedirect(), 'anonymous -> accountX should redirect to login');
    }

    // ==================== Funds page ====================

    public function test_beneficiary_can_view_own_fund(): void
    {
        $r = $this->actingAs($this->beneficiaryX)->get('/funds/' . $this->fundX->id);
        $r->assertOk();
    }

    public function test_beneficiary_cannot_view_another_fund(): void
    {
        $r = $this->actingAs($this->beneficiaryX)->get('/funds/' . $this->fundY->id);
        $this->assertBlocked($r, $this->fundY->name, 'beneficiaryX -> fundY');
    }

    public function test_unassigned_user_cannot_view_any_fund(): void
    {
        $r = $this->actingAs($this->unassigned)->get('/funds/' . $this->fundX->id);
        $this->assertBlocked($r, $this->fundX->name, 'unassigned -> fundX');
    }

    // ==================== Admin-only data ====================

    public function test_beneficiary_cannot_reach_admin_user_roles(): void
    {
        $r = $this->actingAs($this->beneficiaryX)->get('/admin/user-roles');
        $blocked = $r->status() === 403 || $r->isRedirect();
        $this->assertTrue($blocked, 'beneficiaryX -> /admin/user-roles must be blocked, got ' . $r->status());
    }

    public function test_beneficiary_cannot_reach_credit_line_admin_on_own_account(): void
    {
        // credit_lines.index is gated by ensureAdmin() (is_admin()), so even
        // the owner of the account is blocked from the management view.
        $r = $this->actingAs($this->beneficiaryX)
            ->get('/accounts/' . $this->accountX->id . '/credit-lines');
        $this->assertTrue(
            $r->status() === 403 || $r->isRedirect(),
            'beneficiaryX -> own credit-lines admin view must be blocked, got ' . $r->status()
        );
    }

    public function test_system_admin_can_reach_admin_user_roles(): void
    {
        $r = $this->actingAs($this->systemAdmin)->get('/admin/user-roles');
        $r->assertOk();
    }

    // ==================== Profile ====================

    public function test_profile_page_shows_only_authenticated_users_data(): void
    {
        // There is no id-parameterized profile route — /profile always
        // binds to the auth user. Assert it renders beneficiaryX's email
        // and never beneficiaryY's, so there is no leak vector.
        $r = $this->actingAs($this->beneficiaryX)->get('/profile');
        $r->assertOk();
        $r->assertSee($this->beneficiaryX->email, false);
        $r->assertDontSee($this->beneficiaryY->email, false);
    }

    public function test_anonymous_cannot_reach_profile(): void
    {
        $r = $this->get('/profile');
        $this->assertTrue($r->isRedirect(), 'anonymous -> /profile should redirect to login');
    }
}
