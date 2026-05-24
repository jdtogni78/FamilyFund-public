<?php

namespace Tests\Feature;

use App\Models\AccountBalance;
use App\Models\AccountExt;
use App\Models\AccountReport;
use App\Models\Fund;
use App\Models\FundReport;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\TestFixtures;
use Tests\TestCase;

/**
 * API ACL matrix.
 *
 * Object-level / tenant authorization is now enforced across the generated
 * resource controllers (#13 / #49 item A), mirroring AccountAPIController via
 * App\Http\Controllers\Traits\AuthorizesApiAccess:
 *
 *  - Tenant-scoped (fund/account): funds, accounts, portfolios, transactions,
 *    account_balances, account_matching_rules, transaction_matchings,
 *    fund_reports, account_reports, trade_portfolios/items, portfolio_assets —
 *    scoped index + object-level guards on show/update/destroy.
 *  - PII / system (admin-only): users, people, phones, addresses, id_documents,
 *    scheduled_jobs.
 *  - Shared/reference (non-tenant, no IDOR dimension): assets, asset_prices,
 *    matching_rules, schedules, change_logs, asset_change_logs — auth-locked
 *    only; write-authz hardening tracked in #50 / #51.
 *
 * This suite asserts both the auth boundary (unauthenticated → 401) and the
 * per-role / cross-tenant object-level boundaries for the scoped resources.
 */
class SecurityApiAclMatrixTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Generated API resources whose index + detail GET routes must reject
     * unauthenticated callers. `auth:sanctum` runs before route-model binding,
     * so a placeholder id exercises the auth boundary without seeded rows.
     */
    private const AUTH_REQUIRED_RESOURCES = [
        'accounts',
        'funds',
        'transactions',
        'account_reports',
        'fund_reports',
        'users',
        'people',
        'id_documents',
    ];

    private User $beneficiary;
    private User $fundAdmin;
    private User $financialManager;
    private User $systemAdmin;
    private User $unassigned;

    private AccountExt $ownAccount;
    private AccountExt $siblingAccount;
    private AccountExt $crossFundAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $fund = Fund::factory()->create();
        $crossFund = Fund::factory()->create();

        $users = TestFixtures::aclUsers($fund);
        $crossUsers = TestFixtures::aclUsers($crossFund);

        $this->beneficiary = $users['beneficiary'];
        $this->fundAdmin = $users['fundAdmin'];
        $this->financialManager = $users['financialManager'];
        $this->systemAdmin = $users['systemAdmin'];
        $this->unassigned = $users['unassigned'];
        $this->ownAccount = $users['beneficiaryAccount'];
        $this->crossFundAccount = $crossUsers['beneficiaryAccount'];

        // accounts.code is varchar(15); account auto-increment ids in seeded
        // data can be 6+ digits, so keep the prefix tiny to avoid overflow.
        $this->ownAccount->forceFill([
            'code' => 'AO-' . $this->ownAccount->id,
            'nickname' => 'API Own Account ' . $this->ownAccount->id,
        ])->save();
        $this->crossFundAccount->forceFill([
            'code' => 'AX-' . $this->crossFundAccount->id,
            'nickname' => 'API Cross Fund Account ' . $this->crossFundAccount->id,
        ])->save();

        $sibling = User::factory()->create();
        $this->siblingAccount = AccountExt::create([
            'fund_id' => $fund->id,
            'user_id' => $sibling->id,
            'code' => 'AS-' . $sibling->id,
            'nickname' => 'API Sibling Account ' . $sibling->id,
            'type' => 'individual',
        ]);
    }

    public function test_account_api_detail_acl_matrix(): void
    {
        $cases = [
            'anonymous own account' => [null, $this->ownAccount, [401]],
            'unassigned own account' => [$this->unassigned, $this->ownAccount, [403]],
            'beneficiary own account' => [$this->beneficiary, $this->ownAccount, [200]],
            'beneficiary sibling account' => [$this->beneficiary, $this->siblingAccount, [403, 404]],
            'beneficiary cross-fund account' => [$this->beneficiary, $this->crossFundAccount, [403, 404]],
            'financial manager in-fund sibling account' => [$this->financialManager, $this->siblingAccount, [200]],
            'fund admin in-fund sibling account' => [$this->fundAdmin, $this->siblingAccount, [200]],
            'fund admin cross-fund account' => [$this->fundAdmin, $this->crossFundAccount, [403, 404]],
            'system admin cross-fund account' => [$this->systemAdmin, $this->crossFundAccount, [200]],
        ];

        foreach ($cases as $label => [$user, $account, $expectedStatuses]) {
            $this->resetAuth();
            if ($user) {
                Sanctum::actingAs($user);
            }

            $response = $this->getJson('/api/accounts/' . $account->id);

            $this->assertContains(
                $response->getStatusCode(),
                $expectedStatuses,
                "{$label}: unexpected status for /api/accounts/{$account->id}"
            );

            if ($response->getStatusCode() !== 200) {
                $this->assertStringNotContainsString($account->code, $response->getContent(), "{$label}: blocked response leaked account code.");
                $this->assertStringNotContainsString($account->nickname, $response->getContent(), "{$label}: blocked response leaked account nickname.");
            }
        }
    }

    public function test_account_api_index_does_not_leak_sibling_or_cross_fund_records_to_beneficiary(): void
    {
        Sanctum::actingAs($this->beneficiary);

        $response = $this->getJson('/api/accounts');

        $response->assertOk();
        $response->assertSee($this->ownAccount->code, false);
        $response->assertDontSee($this->siblingAccount->code, false);
        $response->assertDontSee($this->siblingAccount->nickname, false);
        $response->assertDontSee($this->crossFundAccount->code, false);
        $response->assertDontSee($this->crossFundAccount->nickname, false);
    }

    public function test_account_api_index_does_not_leak_cross_fund_records_to_fund_admin(): void
    {
        Sanctum::actingAs($this->fundAdmin);

        $response = $this->getJson('/api/accounts');

        $response->assertOk();
        $response->assertSee($this->ownAccount->code, false);
        $response->assertSee($this->siblingAccount->code, false);
        $response->assertDontSee($this->crossFundAccount->code, false);
        $response->assertDontSee($this->crossFundAccount->nickname, false);
    }

    /**
     * Every generated resource API (index + detail) must reject unauthenticated
     * callers. This is the cross-resource expansion of the account-only matrix:
     * it is the strongest invariant that holds for all resources today, and it
     * trips if any future resource route escapes the `auth:sanctum` lock.
     */
    public function test_target_api_resources_require_authentication(): void
    {
        foreach (self::AUTH_REQUIRED_RESOURCES as $resource) {
            foreach (["/api/{$resource}", "/api/{$resource}/1"] as $url) {
                $this->resetAuth();

                $response = $this->getJson($url);

                $this->assertSame(
                    401,
                    $response->getStatusCode(),
                    "Unauthenticated GET {$url} must be rejected (401), got {$response->getStatusCode()}."
                );
                $this->assertStringNotContainsString(
                    '"success":true',
                    $response->getContent(),
                    "Unauthenticated GET {$url} returned a success payload."
                );
            }
        }
    }

    /**
     * /api/funds/{id} must enforce the same object-level matrix as accounts:
     * a fund is visible only to callers with a role in it (and system admins).
     */
    public function test_fund_api_detail_acl_matrix(): void
    {
        $ownFundId = $this->ownAccount->fund_id;
        $crossFundId = $this->crossFundAccount->fund_id;

        $cases = [
            'anonymous own fund' => [null, $ownFundId, [401]],
            'unassigned own fund' => [$this->unassigned, $ownFundId, [403]],
            'beneficiary own fund' => [$this->beneficiary, $ownFundId, [200]],
            'beneficiary cross-fund' => [$this->beneficiary, $crossFundId, [403, 404]],
            'fund admin own fund' => [$this->fundAdmin, $ownFundId, [200]],
            'fund admin cross-fund' => [$this->fundAdmin, $crossFundId, [403, 404]],
            'system admin cross-fund' => [$this->systemAdmin, $crossFundId, [200]],
        ];

        foreach ($cases as $label => [$user, $fundId, $expected]) {
            $this->resetAuth();
            if ($user) {
                Sanctum::actingAs($user);
            }

            $response = $this->getJson('/api/funds/' . $fundId);

            $this->assertContains(
                $response->getStatusCode(),
                $expected,
                "{$label}: unexpected status for /api/funds/{$fundId}"
            );
        }
    }

    /**
     * Account-owned resources (transactions, balances, reports) must not be
     * readable for a sibling or cross-fund account through their detail routes.
     */
    public function test_account_owned_resources_reject_cross_tenant_reads(): void
    {
        $siblingTxn = Transaction::factory()->create(['account_id' => $this->siblingAccount->id]);
        $crossTxn = Transaction::factory()->create(['account_id' => $this->crossFundAccount->id]);
        $siblingBalance = AccountBalance::factory()->create(['account_id' => $this->siblingAccount->id]);
        $siblingReport = AccountReport::factory()->create([
            'account_id' => $this->siblingAccount->id,
            'type' => 'ALL',
            'as_of' => '2026-01-15',
        ]);

        $this->resetAuth();
        Sanctum::actingAs($this->beneficiary);

        $routes = [
            '/api/transactions/' . $siblingTxn->id,
            '/api/transactions/' . $crossTxn->id,
            '/api/account_balances/' . $siblingBalance->id,
            '/api/account_reports/' . $siblingReport->id,
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $this->assertContains(
                $response->getStatusCode(),
                [403, 404],
                "Beneficiary must not read another account's data via {$route}."
            );
        }
    }

    /**
     * Fund-owned resources (fund reports) must not be readable across funds.
     */
    public function test_fund_reports_reject_cross_tenant_reads(): void
    {
        $crossFundReport = FundReport::factory()->create([
            'fund_id' => $this->crossFundAccount->fund_id,
            'type' => 'ALL',
            'as_of' => '2026-01-15',
        ]);

        $this->resetAuth();
        Sanctum::actingAs($this->beneficiary);

        $response = $this->getJson('/api/fund_reports/' . $crossFundReport->id);

        $this->assertContains(
            $response->getStatusCode(),
            [403, 404],
            'Beneficiary must not read a cross-fund fund report.'
        );
    }

    /**
     * Scoped index endpoints must not leak sibling / cross-fund rows to a
     * beneficiary (object-level scoping applied to the listing query).
     */
    public function test_scoped_index_does_not_leak_cross_tenant_records_to_beneficiary(): void
    {
        $ownTxn = Transaction::factory()->create(['account_id' => $this->ownAccount->id]);
        $siblingTxn = Transaction::factory()->create(['account_id' => $this->siblingAccount->id]);
        $crossTxn = Transaction::factory()->create(['account_id' => $this->crossFundAccount->id]);

        $this->resetAuth();
        Sanctum::actingAs($this->beneficiary);

        $response = $this->getJson('/api/transactions');
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($ownTxn->id, $ids, 'Beneficiary should see their own transaction.');
        $this->assertNotContains($siblingTxn->id, $ids, 'Beneficiary must not see a sibling transaction.');
        $this->assertNotContains($crossTxn->id, $ids, 'Beneficiary must not see a cross-fund transaction.');
    }

    /**
     * PII / system resources have no legitimate non-admin API consumer and are
     * restricted to system admins.
     */
    public function test_pii_and_system_resources_are_admin_only(): void
    {
        $adminOnly = [
            '/api/users',
            '/api/people',
            '/api/phones',
            '/api/addresses',
            '/api/id_documents',
            '/api/scheduled_jobs',
        ];

        $this->resetAuth();
        Sanctum::actingAs($this->beneficiary);
        foreach ($adminOnly as $url) {
            $response = $this->getJson($url);
            $this->assertSame(
                403,
                $response->getStatusCode(),
                "{$url} must be admin-only (beneficiary should get 403)."
            );
        }

        $this->resetAuth();
        Sanctum::actingAs($this->systemAdmin);
        foreach ($adminOnly as $url) {
            $response = $this->getJson($url);
            $this->assertSame(
                200,
                $response->getStatusCode(),
                "{$url} should be reachable by a system admin (got {$response->getStatusCode()})."
            );
        }
    }

    private function resetAuth(): void
    {
        auth()->forgetGuards();
        $this->app['auth']->forgetGuards();
    }
}
