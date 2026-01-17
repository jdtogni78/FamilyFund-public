<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use App\Repositories\GoalRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for GoalControllerExt
 * Target: Push from 46.88% to 50%+
 */
class GoalControllerExtTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected GoalRepository $goalRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->goalRepo = app(GoalRepository::class);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_index_displays_goals_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertViewIs('goals.index');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('goals.create'));

        $response->assertStatus(200);
        $response->assertViewIs('goals.create');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('goals.show', 99999));

        $response->assertRedirect(route('goals.index'));
    }
}
