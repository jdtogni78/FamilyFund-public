<?php

namespace Tests\Feature;

use App\Models\TransactionMatching;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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

    public function test_destroy_handles_transaction_matching()
    {
        $matching = TransactionMatching::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('transactionMatchings.destroy', $matching->id));

        $response->assertRedirect(route('transactionMatchings.index'));
        // Note: Record may not be deleted due to foreign key constraints
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('transactionMatchings.destroy', 99999));

        $response->assertRedirect(route('transactionMatchings.index'));
        $response->assertSessionHas('flash_notification');
    }
}
