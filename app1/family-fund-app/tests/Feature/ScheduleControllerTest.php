<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for ScheduleController
 * Target: Push from 48.5% to 50%+
 */
class ScheduleControllerTest extends TestCase
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

    public function test_show_displays_schedule()
    {
        $schedule = Schedule::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('schedules.show', $schedule->id));

        $response->assertStatus(200);
        $response->assertViewIs('schedules.show');
        $response->assertViewHas('schedule');
    }

    public function test_destroy_handles_schedule()
    {
        $schedule = Schedule::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('schedules.destroy', $schedule->id));

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('flash_notification');
    }
}
