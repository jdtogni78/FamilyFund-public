<?php

namespace Tests\Feature;

use App\Models\TradePortfolioItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for TradePortfolioItemController
 * Target: Push from 47.1% to 50%+
 */
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

    public function test_show_displays_trade_portfolio_item()
    {
        $tradePortfolioItem = TradePortfolioItem::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('tradePortfolioItems.show', $tradePortfolioItem->id));

        $response->assertStatus(200);
        $response->assertViewIs('trade_portfolio_items.show');
        $response->assertViewHas('tradePortfolioItem');
    }

    public function test_destroy_handles_trade_portfolio_item()
    {
        $tradePortfolioItem = TradePortfolioItem::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('tradePortfolioItems.destroy', $tradePortfolioItem->id));

        $response->assertRedirect();
        $response->assertSessionHas('flash_notification');
    }
}
