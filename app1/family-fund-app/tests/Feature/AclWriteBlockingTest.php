<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Fixtures\TestFixtures;
use Tests\TestCase;

/**
 * HTTP-level WRITE authorization for the generated/admin CRUD surfaces.
 *
 * AclMatrixTest sweeps every GET route; CrossTenantIsolationTest covers
 * read-side tenant isolation. Neither exercises the write verbs, which is
 * exactly where an unprivileged user could mutate fund-wide data. This file
 * fires POST/PUT/DELETE as each role and asserts the two enforcement
 * mechanisms hold:
 *
 *   1. The `fund.full` middleware (RequireFullFundAccess) wraps the admin /
 *      deposit / PII resource controllers. Only the "full access" roles
 *      (system-admin, fund-admin, financial-manager) get through; beneficiary
 *      and unassigned users get 403, anonymous is redirected to login. Because
 *      the middleware runs before the controller, the 403 is deterministic and
 *      does not depend on the target row existing — so a throwaway id is fine.
 *
 *   2. TransactionPolicy gates transaction create/update/delete. Transactions
 *      are deliberately NOT middleware-gated (a beneficiary must still be able
 *      to *view* their own), so write authorization runs through the policy
 *      instead. The create form is the cleanest probe of the create ability.
 *
 * "Blocked" here means a hard 403 for an authenticated-but-unauthorized user;
 * the pre-controller middlewares (SetFundPermissions, EnsureTwoFactorIsCompleted)
 * are no-ops in this context, so there is no redirect to muddy the result.
 */
class AclWriteBlockingTest extends TestCase
{
    use DatabaseTransactions;

    private Fund $fund;
    private User $beneficiary;
    private User $unassigned;
    private User $financialManager;
    private User $fundAdmin;

    /**
     * Resource base paths gated by the fund.full middleware. Every write verb
     * must 403 for a blocked role and must NOT 403 for a full-access role.
     */
    private const FUND_FULL_RESOURCES = [
        // The originally targeted asset / job / schedule surfaces.
        'assets', 'assetPrices', 'assetChangeLogs',
        'scheduledJobs', 'schedules',
        // Portfolio / trade admin surfaces.
        'portfolios', 'portfolioAssets',
        'tradePortfolios', 'tradePortfolioItems',
        'transactionMatchings', 'matchingRules',
        // Account-admin data.
        'accountBalances', 'accountGoals', 'accountMatchingRules', 'accountReports',
        // Reports & goals.
        'changeLogs', 'fundReports', 'goals', 'tradeBandReports',
        // Deposit flows.
        'cashDeposits', 'depositRequests',
        // PII / identity.
        'addresses', 'persons', 'people', 'phones', 'id_documents', 'users',
    ];

    /**
     * Custom (non-resource) write routes belonging to the same gated
     * controllers. Left ungated, these would bypass the resource-level gate.
     * Each entry is [verb, uri]; a throwaway id is enough since the gate fires
     * before model binding.
     */
    private const FUND_FULL_CUSTOM_WRITES = [
        ['post', '/cashDeposits/999999/assign'],        // cashDeposits.do_assign
        ['post', '/accountMatchingRules/store_bulk'],    // accountMatchingRules.store_bulk
        ['post', '/fundReports/999999/resend'],          // fundReports.resend
        ['post', '/tradeBandReports/999999/resend'],     // tradeBandReports.resend
        ['post', '/tradePortfolios/999999/rebalance'],   // tradePortfolios.doRebalance
        ['post', '/tradePortfolios/999999/do_deposits'], // tradePortfolios.do_deposits
        ['post', '/matchingRules/store_clone'],          // matchingRules.store_clone
    ];

    /**
     * Representative subset (the literal asset/job/schedule scope) used for the
     * positive controls — generated CRUD controllers with no extra in-method
     * gating, so a full-access role reliably gets past fund.full.
     */
    private const HEADLINE_RESOURCES = [
        'assets', 'assetPrices', 'scheduledJobs', 'schedules',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->fund = Fund::factory()->create();
        $u = TestFixtures::aclUsers($this->fund);
        $this->beneficiary      = $u['beneficiary'];
        $this->unassigned       = $u['unassigned'];
        $this->financialManager = $u['financialManager'];
        $this->fundAdmin        = $u['fundAdmin'];
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    /** The write verbs a blocked user might try against a resource. */
    private function writeVerbsFor(string $resource): array
    {
        return [
            ['post', "/$resource"],
            ['put', "/$resource/999999"],
            ['delete', "/$resource/999999"],
        ];
    }

    // ==================== fund.full: blocked roles ====================

    public function test_beneficiary_is_forbidden_from_every_fund_full_write(): void
    {
        foreach (self::FUND_FULL_RESOURCES as $res) {
            foreach ($this->writeVerbsFor($res) as [$verb, $uri]) {
                $r = $this->actingAs($this->beneficiary)->$verb($uri);
                $this->assertSame(403, $r->status(), "beneficiary $verb $uri must be 403");
            }
        }
    }

    public function test_unassigned_user_is_forbidden_from_every_fund_full_write(): void
    {
        foreach (self::FUND_FULL_RESOURCES as $res) {
            foreach ($this->writeVerbsFor($res) as [$verb, $uri]) {
                $r = $this->actingAs($this->unassigned)->$verb($uri);
                $this->assertSame(403, $r->status(), "unassigned $verb $uri must be 403");
            }
        }
    }

    public function test_anonymous_is_redirected_from_every_fund_full_write(): void
    {
        foreach (self::FUND_FULL_RESOURCES as $res) {
            foreach ($this->writeVerbsFor($res) as [$verb, $uri]) {
                $r = $this->$verb($uri);
                $this->assertTrue(
                    $r->isRedirect(),
                    "anonymous $verb $uri must redirect to login, got {$r->status()}"
                );
            }
        }
    }

    public function test_beneficiary_is_forbidden_from_custom_admin_write_routes(): void
    {
        foreach (self::FUND_FULL_CUSTOM_WRITES as [$verb, $uri]) {
            $r = $this->actingAs($this->beneficiary)->$verb($uri);
            $this->assertSame(403, $r->status(), "beneficiary $verb $uri must be 403");
        }
    }

    public function test_unassigned_user_is_forbidden_from_custom_admin_write_routes(): void
    {
        foreach (self::FUND_FULL_CUSTOM_WRITES as [$verb, $uri]) {
            $r = $this->actingAs($this->unassigned)->$verb($uri);
            $this->assertSame(403, $r->status(), "unassigned $verb $uri must be 403");
        }
    }

    public function test_beneficiary_is_forbidden_from_gated_admin_reads(): void
    {
        // Resource-level gating also hides the admin index/create surfaces,
        // which is intentional for the deposit/PII controllers.
        foreach (self::FUND_FULL_RESOURCES as $res) {
            foreach (["/$res", "/$res/create"] as $uri) {
                $r = $this->actingAs($this->beneficiary)->get($uri);
                $this->assertSame(403, $r->status(), "beneficiary GET $uri must be 403");
            }
        }
    }

    // ==================== fund.full: full-access roles pass ====================

    public function test_full_access_roles_pass_the_fund_full_gate_on_writes(): void
    {
        // fund-admin and financial-manager are in the 'full' bucket, so the
        // gate must let every write verb reach the controller. The empty body
        // then yields a redirect / validation error / 500 — anything but the
        // 403 the gate would have produced.
        foreach ([$this->fundAdmin, $this->financialManager] as $user) {
            foreach (self::HEADLINE_RESOURCES as $res) {
                foreach ($this->writeVerbsFor($res) as [$verb, $uri]) {
                    $r = $this->actingAs($user)->$verb($uri);
                    $this->assertNotSame(
                        403,
                        $r->status(),
                        "{$user->email} $verb $uri must not be blocked by fund.full"
                    );
                }
            }
        }
    }

    public function test_full_access_roles_can_reach_gated_admin_surfaces(): void
    {
        foreach ([$this->fundAdmin, $this->financialManager] as $user) {
            foreach (self::HEADLINE_RESOURCES as $res) {
                foreach (["/$res", "/$res/create"] as $uri) {
                    $r = $this->actingAs($user)->get($uri);
                    $this->assertNotSame(
                        403,
                        $r->status(),
                        "{$user->email} GET $uri must not be blocked by fund.full"
                    );
                }
            }
        }
    }

    // ==================== transactions: policy-gated writes ====================

    public function test_beneficiary_cannot_reach_transaction_create_form(): void
    {
        // /transactions is not middleware-gated (a beneficiary can view their
        // own), so creation is gated by TransactionPolicy::create. The create
        // form authorizes that same ability without needing a valid payload.
        $r = $this->actingAs($this->beneficiary)->get('/transactions/create');
        $this->assertSame(403, $r->status());
    }

    public function test_unassigned_user_cannot_reach_transaction_create_form(): void
    {
        $r = $this->actingAs($this->unassigned)->get('/transactions/create');
        $this->assertSame(403, $r->status());
    }

    public function test_financial_manager_can_reach_transaction_create_form(): void
    {
        $r = $this->actingAs($this->financialManager)->get('/transactions/create');
        $this->assertNotSame(403, $r->status(), 'financial-manager must pass TransactionPolicy::create');
    }
}
