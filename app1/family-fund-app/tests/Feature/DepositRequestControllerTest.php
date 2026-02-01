<?php

namespace Tests\Feature;

use App\Models\DepositRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for DepositRequestController
 * Target: Push from 45.5% to 50%+
 */
class DepositRequestControllerTest extends TestCase
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

    public function test_show_displays_deposit_request()
    {
        $account = \App\Models\Account::factory()->create();
        $depositRequest = DepositRequest::factory()->create([
            'account_id' => $account->id,
            'amount' => 1000
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('depositRequests.show', $depositRequest->id));

        $response->assertStatus(200);
        $response->assertViewIs('deposit_requests.show');
        $response->assertViewHas('depositRequest');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('depositRequests.show', 99999));

        $response->assertRedirect(route('depositRequests.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_deposit_request()
    {
        $account = \App\Models\Account::factory()->create();
        $depositRequest = DepositRequest::factory()->create([
            'account_id' => $account->id,
            'amount' => 1000
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('depositRequests.destroy', $depositRequest->id));

        $response->assertRedirect(route('depositRequests.index'));
        $response->assertSessionHas('flash_notification');
    }
}
