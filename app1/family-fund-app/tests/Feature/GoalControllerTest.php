<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for GoalController
 * Target: Push from 48% to 50%+
 */
class GoalControllerTest extends TestCase
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

    public function test_destroy_deletes_goal()
    {
        $goal = Goal::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('goals.destroy', $goal->id));

        $response->assertRedirect(route('goals.index'));
        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('goals.destroy', 99999));

        $response->assertRedirect(route('goals.index'));
        $response->assertSessionHas('flash_notification');
    }
}
