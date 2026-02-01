<?php

namespace Tests\Feature;

use App\Models\TradePortfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for TradePortfolioController
 * Target: Push from 44.1% to 50%+
 */
class TradePortfolioControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_show_displays_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('tradePortfolios.show', $tradePortfolio->id));

        $response->assertStatus(200);
        $response->assertViewIs('trade_portfolios.show');
        $response->assertViewHas('tradePortfolio');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('tradePortfolios.show', 99999));

        $response->assertRedirect(route('tradePortfolios.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('tradePortfolios.destroy', $tradePortfolio->id));

        $response->assertRedirect();
        $response->assertSessionHas('flash_notification');
    }
}
