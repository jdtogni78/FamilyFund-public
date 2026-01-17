<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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

    public function test_destroy_deletes_schedule()
    {
        $schedule = Schedule::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('schedules.destroy', $schedule->id));

        $response->assertRedirect(route('schedules.index'));
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('schedules.destroy', 99999));

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('flash_notification');
    }
}
