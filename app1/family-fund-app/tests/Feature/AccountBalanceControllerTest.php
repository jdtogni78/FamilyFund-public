<?php

namespace Tests\Feature;

use App\Models\AccountBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for AccountBalanceController
 * Target: Push from 48.5% to 50%+
 */
class AccountBalanceControllerTest extends TestCase
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

    public function test_show_displays_account_balance()
    {
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('accountBalances.show', $accountBalance->id));

        $response->assertStatus(200);
        $response->assertViewIs('account_balances.show');
        $response->assertViewHas('accountBalance');
    }

    public function test_destroy_handles_account_balance()
    {
        $accountBalance = AccountBalance::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('accountBalances.destroy', $accountBalance->id));

        $response->assertRedirect(route('accountBalances.index'));
        $response->assertSessionHas('flash_notification');
    }
}
