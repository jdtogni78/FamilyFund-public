<?php

namespace Tests\Feature;

use App\Models\AccountExt;
use App\Models\AccountReport;
use App\Models\Fund;
use App\Models\FundReport;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Fixtures\TestFixtures;
use Tests\TestCase;

class SecurityAccessRegressionTest extends TestCase
{
    use DatabaseTransactions;

    private User $beneficiary;
    private AccountExt $beneficiaryAccount;
    private AccountExt $siblingAccount;
    private AccountReport $siblingAccountReport;
    private FundReport $fundReport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $fund = Fund::factory()->create();
        $users = TestFixtures::aclUsers($fund);

        $this->beneficiary = $users['beneficiary'];
        $this->beneficiaryAccount = $users['beneficiaryAccount'];

        $siblingUser = User::factory()->create();
        $this->siblingAccount = AccountExt::create([
            'fund_id' => $fund->id,
            'user_id' => $siblingUser->id,
            'code' => 'SIB-' . $siblingUser->id,
            'nickname' => 'Sibling Account',
        ]);

        $this->siblingAccountReport = AccountReport::factory()->create([
            'account_id' => $this->siblingAccount->id,
            'type' => 'ALL',
            'as_of' => '2026-01-15',
        ]);

        $this->fundReport = FundReport::factory()->create([
            'fund_id' => $fund->id,
            'type' => 'ALL',
            'as_of' => '2026-01-15',
        ]);
    }

    public function test_generated_api_routes_reject_anonymous_financial_data_reads(): void
    {
        $routes = [
            '/api/accounts',
            '/api/accounts/' . $this->beneficiaryAccount->id,
            '/api/transactions',
            '/api/users',
            '/api/account_reports',
            '/api/fund_reports',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);

            $this->assertContains(
                $response->getStatusCode(),
                [401, 403],
                "{$route} should not expose data to anonymous callers."
            );
        }
    }

    public function test_beneficiary_cannot_fetch_sibling_account_through_generated_api(): void
    {
        $response = $this
            ->actingAs($this->beneficiary)
            ->getJson('/api/accounts/' . $this->siblingAccount->id);

        $this->assertContains(
            $response->getStatusCode(),
            [403, 404],
            'Beneficiaries must not fetch same-fund sibling accounts through the generated API.'
        );
    }

    public function test_beneficiary_cannot_view_sibling_account_report_page(): void
    {
        $response = $this
            ->actingAs($this->beneficiary)
            ->get('/accountReports/' . $this->siblingAccountReport->id);

        $this->assertContains(
            $response->getStatusCode(),
            [403, 404],
            'Beneficiaries must not view account reports for sibling accounts.'
        );
    }

    public function test_beneficiary_cannot_view_fund_report_page_that_lists_sibling_accounts(): void
    {
        $response = $this
            ->actingAs($this->beneficiary)
            ->get('/fundReports/' . $this->fundReport->id);

        $this->assertContains(
            $response->getStatusCode(),
            [403, 404],
            'Beneficiaries must not view fund-wide report pages that expose sibling accounts.'
        );
    }
}
