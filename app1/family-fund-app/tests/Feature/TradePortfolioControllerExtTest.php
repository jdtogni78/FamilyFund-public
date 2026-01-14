<?php

namespace Tests\Feature;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();
        $this->user = $this->df->user;

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
        $tp = TradePortfolio::first();
        if (!$tp) {
            $this->markTestSkipped('No trade portfolios in database');
        }

        $response = $this->actingAs($this->user)->get('/tradePortfolios/' . $tp->id);

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
        $tp = TradePortfolio::first();
        if (!$tp) {
            $this->markTestSkipped('No trade portfolios in database');
        }

        $response = $this->actingAs($this->user)->get('/tradePortfolios/' . $tp->id . '/split');

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
        $tp = TradePortfolio::first();
        if (!$tp) {
            $this->markTestSkipped('No trade portfolios in database');
        }

        $start = now()->subDays(30)->format('Y-m-d');
        $end = now()->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/tradePortfolios/' . $tp->id . '/rebalance/' . $start . '/' . $end);

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
        // Test validation by submitting without portfolio_id
        $response = $this->actingAs($this->user)->post('/tradePortfolios', [
            'start_dt' => '2024-01-01',
            'end_dt' => '9999-12-31',
        ]);

        // Should have validation error for portfolio_id
        $response->assertSessionHasErrors(['portfolio_id']);
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form()
    {
        $tp = TradePortfolio::first();
        if (!$tp) {
            $this->markTestSkipped('No trade portfolios in database');
        }

        $response = $this->actingAs($this->user)->get('/tradePortfolios/' . $tp->id . '/edit');

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
        $tp = TradePortfolio::first();
        if (!$tp) {
            $this->markTestSkipped('No trade portfolios in database');
        }

        // Test validation by submitting without portfolio_id
        $response = $this->actingAs($this->user)->put('/tradePortfolios/' . $tp->id, [
            'start_dt' => '2024-01-01',
            'end_dt' => '2025-06-30',
        ]);

        $response->assertSessionHasErrors(['portfolio_id']);
    }

    public function test_update_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->put('/tradePortfolios/99999', [
            'portfolio_id' => 1,
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

