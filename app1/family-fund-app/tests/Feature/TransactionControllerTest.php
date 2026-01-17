<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for TransactionController
 * Target: Push from 34.3% to 50%+
 */
class TransactionControllerTest extends TestCase
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

    public function test_index_displays_transactions_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('transactions.index');
        $response->assertViewHas('transactions');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.create'));

        $response->assertStatus(200);
        $response->assertViewIs('transactions.create');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.show', 99999));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.edit', 99999));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('transactions.destroy', 99999));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }
}
