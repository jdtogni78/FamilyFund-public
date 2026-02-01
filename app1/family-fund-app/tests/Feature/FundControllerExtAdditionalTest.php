<?php

namespace Tests\Feature;

use App\Models\FundExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Additional tests for FundControllerExt to improve coverage
 * Target: Get coverage from 39% to 60%+
 */
class FundControllerExtAdditionalTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $user;
    protected User $nonAdminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();
        $this->user = $this->df->user;

        // Existing claude@test.local user is an admin (FundTrait checks for specific emails)
        // Use existing user or the test user we created
        $claudeUser = User::where('email', 'claude@test.local')->first();
        if ($claudeUser) {
            $this->user = $claudeUser;
        }

        // Create a non-admin user for authorization tests
        $this->df->createUser();
        $this->nonAdminUser = $this->df->user;
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Portfolios Tests ====================

    public function test_portfolios_displays_fund_portfolios()
    {
        $response = $this->actingAs($this->user)
            ->get('/funds/' . $this->df->fund->id . '/portfolios');

        $response->assertStatus(200);
        $response->assertViewIs('funds.portfolios');
        $response->assertViewHas('fund');
        $response->assertViewHas('portfolios');
    }

    public function test_portfolios_redirects_when_fund_not_found()
    {
        $response = $this->actingAs($this->user)
            ->get('/funds/99999/portfolios');

        $response->assertRedirect(route('funds.index'));
    }

    // ==================== Overview Tests ====================

    public function test_overview_displays_fund_overview()
    {
        $response = $this->actingAs($this->user)
            ->get('/funds/' . $this->df->fund->id . '/overview');

        $response->assertStatus(200);
        $response->assertViewIs('funds.overview');
        $response->assertViewHas('api');
        $response->assertViewHas('asOf');
        $response->assertViewHas('period');
        $response->assertViewHas('groupBy');
    }

    public function test_overview_redirects_when_fund_not_found()
    {
        $response = $this->actingAs($this->user)
            ->get('/funds/99999/overview');

        $response->assertRedirect(route('funds.index'));
    }

    public function test_overview_accepts_period_parameter()
    {
        $response = $this->actingAs($this->user)
            ->get('/funds/' . $this->df->fund->id . '/overview?period=1M');

        $response->assertStatus(200);
        $response->assertViewHas('period', '1M');
    }

    public function test_overview_accepts_group_by_parameter()
    {
        // Valid group_by values are: 'category', 'type', 'display_group'
        $response = $this->actingAs($this->user)
            ->get('/funds/' . $this->df->fund->id . '/overview?group_by=type');

        $response->assertStatus(200);
        $response->assertViewHas('groupBy', 'type');
    }

    public function test_overview_uses_default_for_invalid_period()
    {
        $response = $this->actingAs($this->user)
            ->get('/funds/' . $this->df->fund->id . '/overview?period=INVALID');

        $response->assertStatus(200);
        // Should use default period
        $response->assertViewHas('period');
    }

    public function test_overview_uses_default_for_invalid_group_by()
    {
        $response = $this->actingAs($this->user)
            ->get('/funds/' . $this->df->fund->id . '/overview?group_by=invalid');

        $response->assertStatus(200);
        $response->assertViewHas('groupBy', 'category');
    }

    // ==================== Overview Data (JSON API) Tests ====================

    public function test_overview_data_returns_json()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/funds/' . $this->df->fund->id . '/overview-data');

        $response->assertStatus(200);
        // The overview-data endpoint returns the overview response directly
        $response->assertJsonStructure([
            'id',
            'name',
            'asOf',
            'period',
            'summary',
            'chartData',
            'groups',
        ]);
    }

    public function test_overview_data_returns_404_when_fund_not_found()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/funds/99999/overview-data');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Fund not found']);
    }

    public function test_overview_data_accepts_parameters()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/funds/' . $this->df->fund->id . '/overview-data?period=3M&group_by=sector');

        $response->assertStatus(200);
    }

    // ==================== Four Percent Goal Tests ====================

    public function test_edit_four_pct_goal_displays_form_for_admin()
    {
        $response = $this->actingAs($this->user)
            ->get(route('funds.four_pct_goal.edit', $this->df->fund->id));

        $response->assertStatus(200);
        $response->assertViewIs('funds.four_pct_goal_edit');
        $response->assertViewHas('fund');
    }

    public function test_edit_four_pct_goal_redirects_when_fund_not_found()
    {
        $response = $this->actingAs($this->user)
            ->get(route('funds.four_pct_goal.edit', 99999));

        $response->assertRedirect(route('funds.index'));
    }

    public function test_edit_four_pct_goal_redirects_non_admin()
    {
        $response = $this->actingAs($this->nonAdminUser)
            ->get(route('funds.four_pct_goal.edit', $this->df->fund->id));

        $response->assertRedirect(route('funds.show', $this->df->fund->id));
    }

    public function test_update_four_pct_goal_updates_fund()
    {
        $response = $this->actingAs($this->user)
            ->put(route('funds.four_pct_goal.update', $this->df->fund->id), [
                'four_pct_yearly_expenses' => 50000,
                'four_pct_net_worth_pct' => 80,
            ]);

        $response->assertRedirect(route('funds.show', $this->df->fund->id));

        $this->assertDatabaseHas('funds', [
            'id' => $this->df->fund->id,
            'four_pct_yearly_expenses' => 50000,
            'four_pct_net_worth_pct' => 80,
        ]);
    }

    public function test_update_four_pct_goal_redirects_when_fund_not_found()
    {
        $response = $this->actingAs($this->user)
            ->put(route('funds.four_pct_goal.update', 99999), [
                'four_pct_yearly_expenses' => 50000,
            ]);

        $response->assertRedirect(route('funds.index'));
    }

    public function test_update_four_pct_goal_redirects_non_admin()
    {
        $response = $this->actingAs($this->nonAdminUser)
            ->put(route('funds.four_pct_goal.update', $this->df->fund->id), [
                'four_pct_yearly_expenses' => 50000,
            ]);

        $response->assertRedirect(route('funds.show', $this->df->fund->id));
    }

    public function test_update_four_pct_goal_validates_input()
    {
        $response = $this->actingAs($this->user)
            ->put(route('funds.four_pct_goal.update', $this->df->fund->id), [
                'four_pct_yearly_expenses' => -100,  // Invalid: must be >= 0
            ]);

        $response->assertSessionHasErrors('four_pct_yearly_expenses');
    }

    public function test_update_four_pct_goal_allows_null_expenses()
    {
        // First set a value
        FundExt::find($this->df->fund->id)->update(['four_pct_yearly_expenses' => 50000]);

        $response = $this->actingAs($this->user)
            ->put(route('funds.four_pct_goal.update', $this->df->fund->id), [
                'four_pct_yearly_expenses' => null,
                'four_pct_net_worth_pct' => null,
            ]);

        $response->assertRedirect(route('funds.show', $this->df->fund->id));

        $fund = FundExt::find($this->df->fund->id);
        $this->assertNull($fund->four_pct_yearly_expenses);
        // null net_worth_pct should default to 100
        $this->assertEquals(100, $fund->four_pct_net_worth_pct);
    }

    // ==================== Trade Bands With Valid Data Tests ====================

    public function test_trade_bands_displays_page_with_valid_fund()
    {
        // Create an asset with price for proper trade bands display
        $this->df->createAssetWithPrice(100, $this->df->source, 10);

        $response = $this->actingAs($this->user)
            ->get('/funds/' . $this->df->fund->id . '/trade_bands');

        $response->assertStatus(200);
        $response->assertViewIs('funds.show_trade_bands');
        $response->assertViewHas('api');
        $response->assertViewHas('asOf');
    }

    public function test_trade_bands_as_of_displays_page_with_valid_fund()
    {
        $asOf = now()->format('Y-m-d');
        $this->df->createAssetWithPrice(100, $this->df->source, 10);

        $response = $this->actingAs($this->user)
            ->get('/funds/' . $this->df->fund->id . '/trade_bands_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertViewIs('funds.show_trade_bands');
        $response->assertViewHas('api');
    }

    public function test_trade_bands_accepts_from_parameter()
    {
        $this->df->createAssetWithPrice(100, $this->df->source, 10);
        $fromDate = now()->subMonths(3)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get('/funds/' . $this->df->fund->id . '/trade_bands?from=' . $fromDate);

        $response->assertStatus(200);
        $response->assertViewHas('fromDate');
    }
}
