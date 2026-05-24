<?php

namespace Tests\Feature;

use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\TestFixtures;
use Tests\TestCase;

/**
 * API ACL matrix.
 *
 * Account API access is covered in depth below (object-level authorization is
 * enforced by AccountAPIController). The remaining generated resource
 * controllers (funds, transactions, account/fund reports, users, people, id
 * documents) are currently only protected by the `auth:sanctum` route lock and
 * do NOT yet enforce object-level / tenant scoping — see issue #49 (item A).
 * Until that lands, the cross-resource coverage here asserts the one boundary
 * that IS enforced for every resource: unauthenticated requests are rejected.
 * The per-role / response-body cross-tenant assertions for those resources
 * should be added alongside the scoping fixes in #49.
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

    private function resetAuth(): void
    {
        auth()->forgetGuards();
        $this->app['auth']->forgetGuards();
    }
}
