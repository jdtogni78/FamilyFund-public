<?php

namespace Tests\Feature;

use App\Models\CashDeposit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for CashDepositController (base)
 * Target: Push from 48.5% to 50%+
 */
class CashDepositControllerTest extends TestCase
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

    public function test_show_displays_cash_deposit()
    {
        $account = \App\Models\Account::factory()->create();
        $cashDeposit = CashDeposit::factory()->create(['account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->get(route('cashDeposits.show', $cashDeposit->id));

        $response->assertStatus(200);
        $response->assertViewIs('cash_deposits.show');
        $response->assertViewHas('cashDeposit');
    }

    public function test_destroy_handles_cash_deposit()
    {
        $account = \App\Models\Account::factory()->create();
        $cashDeposit = CashDeposit::factory()->create(['account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('cashDeposits.destroy', $cashDeposit->id));

        $response->assertRedirect(route('cashDeposits.index'));
        $response->assertSessionHas('flash_notification');
    }
}
