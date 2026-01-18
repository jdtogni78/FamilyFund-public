<?php

namespace Tests\Feature;

use App\Models\TransactionMatching;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for TransactionMatchingController
 * Target: Push from 48.5% to 50%+
 */
class TransactionMatchingControllerTest extends TestCase
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

    public function test_show_displays_transaction_matching()
    {
        $transactionMatching = TransactionMatching::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('transactionMatchings.show', $transactionMatching->id));

        $response->assertStatus(200);
        $response->assertViewIs('transaction_matchings.show');
        $response->assertViewHas('transactionMatching');
    }

    public function test_destroy_handles_transaction_matching()
    {
        $transactionMatching = TransactionMatching::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('transactionMatchings.destroy', $transactionMatching->id));

        $response->assertRedirect(route('transactionMatchings.index'));
        $response->assertSessionHas('flash_notification');
    }
}
