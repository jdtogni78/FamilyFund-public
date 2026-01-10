<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\ScheduledJob;

use PHPUnit\Framework\Attributes\Test;
class ScheduledJobApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/scheduled_jobs', $scheduledJob
        );

        $this->assertApiResponse($scheduledJob);
    }

    #[Test]
    public function test_read_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/scheduled_jobs/'.$scheduledJob->id
        );

        $this->assertApiResponse($scheduledJob->toArray());
    }

    #[Test]
    public function test_update_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->create();
        $editedScheduledJob = ScheduledJob::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/scheduled_jobs/'.$scheduledJob->id,
            $editedScheduledJob
        );

        $this->assertApiResponse($editedScheduledJob);
    }

    #[Test]
    public function test_delete_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/scheduled_jobs/'.$scheduledJob->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/scheduled_jobs/'.$scheduledJob->id
        );

        $this->response->assertStatus(404);
    }
}
