<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\TradePortfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for TradePortfolioControllerExt
 * Target: Get coverage from 15% to 50%+
 */
class TradePortfolioControllerExtTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $user;
    protected TradePortfolio $tradePortfolio;

    protected function setUp(): void
    {
        parent::setUp();

        // Create CASH asset required by DataFactory
        Asset::factory()->create([
            'name' => 'CASH',
            'type' => 'CSH',
            'source' => 'MANUAL',
            'display_group' => 'Cash',
        ]);

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();
        $this->user = $this->df->user;

        // Create assets for trade portfolio items
        $asset1 = Asset::factory()->create([
            'name' => 'AAPL',
            'type' => 'STK',
            'source' => 'IB',
            'display_group' => 'Tech Stocks',
        ]);
        $asset2 = Asset::factory()->create([
            'name' => 'GOOGL',
            'type' => 'STK',
            'source' => 'IB',
            'display_group' => 'Tech Stocks',
        ]);
        $asset3 = Asset::factory()->create([
            'name' => 'MSFT',
            'type' => 'STK',
            'source' => 'IB',
            'display_group' => 'Tech Stocks',
        ]);

        // Create a trade portfolio for tests that need it
        $this->tradePortfolio = TradePortfolio::factory()
            ->for($this->df->portfolio, 'portfolio')
            ->create([
                'start_dt' => '2024-01-01',
                'end_dt' => '9999-12-31',
            ]);

        // Create trade portfolio items with the created assets
        \App\Models\TradePortfolioItem::factory()
            ->for($this->tradePortfolio, 'tradePortfolio')
            ->create([
                'symbol' => 'AAPL',
                'type' => 'STK',
                'target_share' => 0.33,
                'deviation_trigger' => 0.05,
            ]);
        \App\Models\TradePortfolioItem::factory()
            ->for($this->tradePortfolio, 'tradePortfolio')
            ->create([
                'symbol' => 'GOOGL',
                'type' => 'STK',
                'target_share' => 0.33,
                'deviation_trigger' => 0.05,
            ]);
        \App\Models\TradePortfolioItem::factory()
            ->for($this->tradePortfolio, 'tradePortfolio')
            ->create([
                'symbol' => 'MSFT',
                'type' => 'STK',
                'target_share' => 0.34,
                'deviation_trigger' => 0.05,
            ]);

        // Give user admin access
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->user->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);

        Mail::fake();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Index Tests ====================

    public function test_index_displays_trade_portfolios()
    {
        $response = $this->actingAs($this->user)->get('/tradePortfolios');

        $response->assertStatus(200);
        $response->assertViewHas('tradePortfolios');
    }

    // ==================== Show Tests ====================

    public function test_show_displays_trade_portfolio()
    {
        $response = $this->actingAs($this->user)->get('/tradePortfolios/' . $this->tradePortfolio->id);

        $response->assertStatus(200);
        $response->assertViewHas('tradePortfolio');
    }

    public function test_show_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/tradePortfolios/99999');

        $response->assertRedirect(route('tradePortfolios.index'));
    }

    // ==================== Split Tests ====================

    public function test_split_displays_form()
    {
        $response = $this->actingAs($this->user)->get('/tradePortfolios/' . $this->tradePortfolio->id . '/split');

        $response->assertStatus(200);
        $response->assertViewHas('tradePortfolio');
        $response->assertViewHas('split', true);
    }

    public function test_split_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/tradePortfolios/99999/split');

        $response->assertRedirect(route('tradePortfolios.index'));
    }

    // ==================== ShowRebalance Tests ====================

    public function test_show_rebalance_with_date_range()
    {
        $start = now()->subDays(30)->format('Y-m-d');
        $end = now()->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/tradePortfolios/' . $this->tradePortfolio->id . '/rebalance/' . $start . '/' . $end);

        $response->assertStatus(200);
        $response->assertViewHas('api');
    }

    public function test_show_rebalance_redirects_when_not_found()
    {
        $start = now()->subDays(30)->format('Y-m-d');
        $end = now()->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/tradePortfolios/99999/rebalance/' . $start . '/' . $end);

        $response->assertRedirect(route('tradePortfolios.index'));
    }

    // Note: show_diff test requires specific data setup (multiple trade portfolios for same portfolio)

    // ==================== Create Tests ====================

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)->get('/tradePortfolios/create');

        $response->assertStatus(200);
    }

    // Note: create_with_params route exists only for tradePortfoliosItems, not tradePortfolios

    // ==================== Store Tests ====================

    public function test_store_validates_required_fields()
    {
        // Test validation by submitting without required fields
        $response = $this->actingAs($this->user)->post('/tradePortfolios', [
            'start_dt' => '2024-01-01',
            'end_dt' => '9999-12-31',
        ]);

        // Should have validation errors for required fields
        $response->assertSessionHasErrors([
            'cash_target',
            'cash_reserve_target',
            'max_single_order',
            'minimum_order',
            'rebalance_period',
            'mode',
        ]);
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form()
    {
        $response = $this->actingAs($this->user)->get('/tradePortfolios/' . $this->tradePortfolio->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewHas('tradePortfolio');
    }

    public function test_edit_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/tradePortfolios/99999/edit');

        $response->assertRedirect(route('tradePortfolios.index'));
    }

    // ==================== Update Tests ====================

    public function test_update_validates_required_fields()
    {
        // Test validation by submitting without required fields
        $response = $this->actingAs($this->user)->put('/tradePortfolios/' . $this->tradePortfolio->id, [
            'start_dt' => '2024-01-01',
            'end_dt' => '2025-06-30',
        ]);

        // Should have validation errors for required fields
        $response->assertSessionHasErrors([
            'cash_target',
            'cash_reserve_target',
            'max_single_order',
            'minimum_order',
            'rebalance_period',
            'mode',
        ]);
    }

    public function test_update_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->put('/tradePortfolios/99999', [
            'portfolio_id' => 1,
            'cash_target' => 0.05,
            'cash_reserve_target' => 0.02,
            'max_single_order' => 5000,
            'minimum_order' => 100,
            'rebalance_period' => 30,
            'mode' => 'STD',
            'start_dt' => '2024-01-01',
            'end_dt' => '2025-12-31',
        ]);

        $response->assertRedirect(route('tradePortfolios.index'));
    }

    // ==================== Destroy Tests ====================

    public function test_destroy_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->delete('/tradePortfolios/99999');

        $response->assertRedirect(route('tradePortfolios.index'));
    }

    // Note: preview_deposits test requires external IBFlex service configuration
}

