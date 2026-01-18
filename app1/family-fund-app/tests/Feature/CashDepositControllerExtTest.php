<?php

namespace Tests\Feature;

use App\Models\CashDeposit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for CashDepositControllerExt
 * Target: Push from 27.3% to 50%+
 */
class CashDepositControllerExtTest extends TestCase
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

    public function test_index_displays_cash_deposits_with_api_data()
    {
        $response = $this->actingAs($this->user)
            ->get(route('cashDeposits.index'));

        $response->assertStatus(200);
        $response->assertViewIs('cash_deposits.index');
        $response->assertViewHas('cashDeposits');
        $response->assertViewHas('api');
    }

    public function test_create_displays_form_with_api_data()
    {
        $response = $this->actingAs($this->user)
            ->get(route('cashDeposits.create'));

        $response->assertStatus(200);
        $response->assertViewIs('cash_deposits.create');
        $response->assertViewHas('api');
    }

    public function test_show_displays_cash_deposit_with_api_data()
    {
        $account = \App\Models\Account::factory()->create();
        $cashDeposit = CashDeposit::factory()->create(['account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->get(route('cashDeposits.show', $cashDeposit->id));

        $response->assertStatus(200);
        $response->assertViewIs('cash_deposits.show');
        $response->assertViewHas('cashDeposit');
        $response->assertViewHas('api');
    }

    public function test_edit_displays_form_with_api_data()
    {
        $account = \App\Models\Account::factory()->create();
        $cashDeposit = CashDeposit::factory()->create(['account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->get(route('cashDeposits.edit', $cashDeposit->id));

        $response->assertStatus(200);
        $response->assertViewIs('cash_deposits.edit');
        $response->assertViewHas('cashDeposit');
        $response->assertViewHas('api');
    }

    public function test_assign_displays_deposit_requests()
    {
        $account = \App\Models\Account::factory()->create();
        $cashDeposit = CashDeposit::factory()->create(['account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->get(route('cashDeposits.assign', $cashDeposit->id));

        $response->assertStatus(200);
        $response->assertViewIs('cash_deposits.assign');
        $response->assertViewHas('cashDeposit');
        $response->assertViewHas('api');
    }
}
