<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for GoalController
 * Target: Push from 48.5% to 50%+
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

    public function test_show_displays_goal()
    {
        $goal = Goal::factory()->create([
            'start_dt' => '2024-01-01',
            'end_dt' => '2024-12-31',
            'target_amount' => 1000,
            'target_pct' => 10
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.show', $goal->id));

        $response->assertStatus(200);
        $response->assertViewIs('goals.show');
        $response->assertViewHas('goal');
    }

    public function test_destroy_handles_goal()
    {
        $goal = Goal::factory()->create([
            'start_dt' => '2024-01-01',
            'end_dt' => '2024-12-31',
            'target_amount' => 1000,
            'target_pct' => 10
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('goals.destroy', $goal->id));

        $response->assertRedirect(route('goals.index'));
        $response->assertSessionHas('flash_notification');
    }
}
