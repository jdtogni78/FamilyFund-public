<?php

namespace Tests\Feature;

use App\Models\CashDeposit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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

    public function test_destroy_deletes_cash_deposit()
    {
        $cashDeposit = CashDeposit::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('cashDeposits.destroy', $cashDeposit->id));

        $response->assertRedirect(route('cashDeposits.index'));
        $this->assertDatabaseMissing('cash_deposits', ['id' => $cashDeposit->id]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('cashDeposits.destroy', 99999));

        $response->assertRedirect(route('cashDeposits.index'));
        $response->assertSessionHas('flash_notification');
    }
}
