<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Smoke tests to verify major pages render without errors.
 * These tests visit routes and verify they return 200 status code.
 *
 * Note: Some pages require complex data setup and are tested separately
 * in their respective feature tests.
 */
class SmokeTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        $this->user = $this->factory->user;

        // Give user system-admin role for smoke tests (fund_id=0 for global access)
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->user->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);
    }

    protected function tearDown(): void
    {
        // Clean up any unclosed output buffers from Livewire/Blade rendering
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Dashboard & Profile ====================

    public function test_dashboard_renders()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_profile_renders()
    {
        $response = $this->actingAs($this->user)->get('/profile');
        $response->assertStatus(200);
    }

    // ==================== Index Pages (List Views) ====================

    public function test_funds_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/funds');
        $response->assertStatus(200);
    }

    public function test_accounts_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/accounts');
        $response->assertStatus(200);
    }

    public function test_transactions_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/transactions');
        $response->assertStatus(200);
    }

    public function test_portfolios_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/portfolios');
        $response->assertStatus(200);
    }

    public function test_trade_portfolios_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/tradePortfolios');
        $response->assertStatus(200);
    }

    public function test_assets_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/assets');
        $response->assertStatus(200);
    }

    public function test_asset_prices_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/assetPrices');
        $response->assertStatus(200);
    }

    public function test_goals_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/goals');
        $response->assertStatus(200);
    }

    public function test_account_balances_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/accountBalances');
        $response->assertStatus(200);
    }

    public function test_fund_reports_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/fundReports');
        $response->assertStatus(200);
    }

    public function test_account_reports_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/accountReports');
        $response->assertStatus(200);
    }

    public function test_matching_rules_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/matchingRules');
        $response->assertStatus(200);
    }

    public function test_account_matching_rules_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/accountMatchingRules');
        $response->assertStatus(200);
    }

    public function test_schedules_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/schedules');
        $response->assertStatus(200);
    }

    public function test_scheduled_jobs_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/scheduledJobs');
        $response->assertStatus(200);
    }

    public function test_users_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/users');
        $response->assertStatus(200);
    }

    public function test_people_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/people');
        $response->assertStatus(200);
    }

    public function test_cash_deposits_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/cashDeposits');
        $response->assertStatus(200);
    }

    public function test_deposit_requests_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/depositRequests');
        $response->assertStatus(200);
    }

    // Note: portfolioReports route does not exist in web.php

    public function test_change_logs_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/changeLogs');
        $response->assertStatus(200);
    }

    public function test_addresses_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/addresses');
        $response->assertStatus(200);
    }

    public function test_phones_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/phones');
        $response->assertStatus(200);
    }

    // ==================== Create Pages ====================

    public function test_funds_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/funds/create');
        $response->assertStatus(200);
    }

    public function test_accounts_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/accounts/create');
        $response->assertStatus(200);
    }

    public function test_transactions_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/transactions/create');
        $response->assertStatus(200);
    }

    public function test_transactions_create_bulk_renders()
    {
        $response = $this->actingAs($this->user)->get('/transactions/create_bulk');
        $response->assertStatus(200);
    }

    public function test_assets_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/assets/create');
        $response->assertStatus(200);
    }

    public function test_goals_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/goals/create');
        $response->assertStatus(200);
    }

    public function test_fund_reports_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/fundReports/create');
        $response->assertStatus(200);
    }

    public function test_matching_rules_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/matchingRules/create');
        $response->assertStatus(200);
    }

    public function test_account_matching_rules_create_bulk_renders()
    {
        $account = $this->factory->userAccount;
        $response = $this->actingAs($this->user)->get('/accountMatchingRules/create_bulk?account=' . $account->id);
        $response->assertStatus(200);
    }

    public function test_schedules_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/schedules/create');
        $response->assertStatus(200);
    }

    // ==================== Show/Detail Pages ====================

    public function test_users_show_renders()
    {
        $response = $this->actingAs($this->user)->get('/users/' . $this->user->id);
        $response->assertStatus(200);
    }

    public function test_portfolios_show_renders()
    {
        $response = $this->actingAs($this->user)->get('/portfolios/' . $this->factory->portfolio->id);
        $response->assertStatus(200);
    }

    public function test_accounts_edit_renders()
    {
        $account = $this->factory->userAccount;
        $response = $this->actingAs($this->user)->get('/accounts/' . $account->id . '/edit');
        $response->assertStatus(200);

        // Verify form fields are populated with account data
        $response->assertSee('value="' . $account->code . '"', false);
        $response->assertSee('value="' . $account->nickname . '"', false);
    }

    public function test_funds_edit_renders()
    {
        $fund = $this->factory->fund;
        $response = $this->actingAs($this->user)->get('/funds/' . $fund->id . '/edit');
        $response->assertStatus(200);

        // Verify form fields are populated with fund data
        $response->assertSee('value="' . $fund->name . '"', false);
    }

    // ==================== Other Pages ====================

    public function test_change_password_renders()
    {
        $response = $this->actingAs($this->user)->get('/change-password');
        $response->assertStatus(200);
    }

    // ==================== Auth Pages (no login required) ====================

    public function test_login_page_renders()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_register_page_renders()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_password_request_page_renders()
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
    }
}
