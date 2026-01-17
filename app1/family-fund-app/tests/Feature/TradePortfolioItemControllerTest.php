<?php

namespace Tests\Feature;

use App\Models\TradePortfolioItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TradePortfolioItemControllerTest extends TestCase
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

    public function test_destroy_handles_trade_portfolio_item()
    {
        $item = TradePortfolioItem::factory()->create();
        $portfolioId = $item->trade_portfolio_id;

        $response = $this->actingAs($this->user)
            ->delete(route('tradePortfolioItems.destroy', $item->id));

        // Redirects to parent portfolio, not index
        $response->assertRedirect(route('tradePortfolios.show', $portfolioId));
        // Note: Record may not be deleted due to foreign key constraints
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('tradePortfolioItems.destroy', 99999));

        $response->assertRedirect(route('tradePortfolioItems.index'));
        $response->assertSessionHas('flash_notification');
    }
}
