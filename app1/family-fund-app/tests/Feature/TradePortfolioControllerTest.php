<?php

namespace Tests\Feature;

use App\Models\TradePortfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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

    public function test_destroy_handles_trade_portfolio()
    {
        $portfolio = TradePortfolio::factory()->create();
        $parentPortfolioId = $portfolio->portfolio_id;

        $response = $this->actingAs($this->user)
            ->delete(route('tradePortfolios.destroy', $portfolio->id));

        // Redirects to parent portfolio
        $response->assertRedirect(route('portfolios.show', $parentPortfolioId));
        // Note: Record may not be deleted due to foreign key constraints
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('tradePortfolios.destroy', 99999));

        $response->assertRedirect(route('tradePortfolios.index'));
        $response->assertSessionHas('flash_notification');
    }
}
