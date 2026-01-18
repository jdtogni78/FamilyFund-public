<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for GoalControllerExt
 * Target: Push from 46.9% to 50%+
 */
class GoalControllerExtTest extends TestCase
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

    public function test_index_displays_goals_with_api_data()
    {
        $response = $this->actingAs($this->user)
            ->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertViewIs('goals.index');
        $response->assertViewHas('goals');
        $response->assertViewHas('api');
    }

    public function test_create_displays_form_with_api_data()
    {
        $response = $this->actingAs($this->user)
            ->get(route('goals.create'));

        $response->assertStatus(200);
        $response->assertViewIs('goals.create');
        $response->assertViewHas('api');
    }

    public function test_show_displays_goal_with_api_data()
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
        $response->assertViewHas('api');
    }
}
