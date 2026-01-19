<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PortfolioControllerTest extends TestCase
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

    public function test_destroy_deletes_portfolio()
    {
        $portfolio = Portfolio::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('portfolios.destroy', $portfolio->id));

        $response->assertRedirect(route('portfolios.index'));
        $this->assertSoftDeleted('portfolios', ['id' => $portfolio->id]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('portfolios.destroy', 99999));

        $response->assertRedirect(route('portfolios.index'));
        $response->assertSessionHas('flash_notification');
    }
}
