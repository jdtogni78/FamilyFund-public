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

class SecurityApiAclMatrixTest extends TestCase
{
    use DatabaseTransactions;

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

        $this->ownAccount->forceFill([
            'code' => 'API-OWN-' . $this->ownAccount->id,
            'nickname' => 'API Own Account ' . $this->ownAccount->id,
        ])->save();
        $this->crossFundAccount->forceFill([
            'code' => 'API-XFUND-' . $this->crossFundAccount->id,
            'nickname' => 'API Cross Fund Account ' . $this->crossFundAccount->id,
        ])->save();

        $sibling = User::factory()->create();
        $this->siblingAccount = AccountExt::create([
            'fund_id' => $fund->id,
            'user_id' => $sibling->id,
            'code' => 'API-SIB-' . $sibling->id,
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

    private function resetAuth(): void
    {
        auth()->forgetGuards();
        $this->app['auth']->forgetGuards();
    }
}
