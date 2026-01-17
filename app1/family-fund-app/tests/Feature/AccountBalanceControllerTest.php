<?php

namespace Tests\Feature;

use App\Models\AccountBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for AccountBalanceController
 * Target: Push from 48% to 50%+
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

    public function test_destroy_deletes_account_balance()
    {
        $balance = AccountBalance::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('accountBalances.destroy', $balance->id));

        $response->assertRedirect(route('accountBalances.index'));
        $this->assertDatabaseMissing('account_balances', ['id' => $balance->id]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('accountBalances.destroy', 99999));

        $response->assertRedirect(route('accountBalances.index'));
        $response->assertSessionHas('flash_notification');
    }
}
