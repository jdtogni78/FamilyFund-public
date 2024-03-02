<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Schedule;

class ScheduleApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_schedule()
    {
        $schedule = Schedule::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/schedules', $schedule
        );

        $this->assertApiResponse($schedule);
    }

    /**
     * @test
     */
    public function test_read_schedule()
    {
        $schedule = Schedule::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/schedules/'.$schedule->id
        );

        $this->assertApiResponse($schedule->toArray());
    }

    /**
     * @test
     */
    public function test_update_schedule()
    {
        $schedule = Schedule::factory()->create();
        $editedSchedule = Schedule::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/schedules/'.$schedule->id,
            $editedSchedule
        );

        $this->assertApiResponse($editedSchedule);
    }

    /**
     * @test
     */
    public function test_delete_schedule()
    {
        $schedule = Schedule::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/schedules/'.$schedule->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/schedules/'.$schedule->id
        );

        $this->response->assertStatus(404);
    }
}
