<?php

namespace Tests\Feature;

use App\Models\AccountGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AccountGoalControllerTest extends TestCase
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

    public function test_destroy_deletes_account_goal()
    {
        $accountGoal = AccountGoal::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('accountGoals.destroy', $accountGoal->id));

        $response->assertRedirect(route('accountGoals.index'));
        $this->assertDatabaseMissing('account_goals', ['id' => $accountGoal->id]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('accountGoals.destroy', 99999));

        $response->assertRedirect(route('accountGoals.index'));
        $response->assertSessionHas('flash_notification');
    }
}
