<?php namespace Tests\Repositories;

use App\Models\ScheduledJob;
use App\Repositories\ScheduledJobRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class ScheduledJobRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var ScheduledJobRepository
     */
    protected $scheduledJobRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->scheduledJobRepo = \App::make(ScheduledJobRepository::class);
    }
    public function test_create_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->make()->toArray();

        $createdScheduledJob = $this->scheduledJobRepo->create($scheduledJob);

        $createdScheduledJob = $createdScheduledJob->toArray();
        $this->assertArrayHasKey('id', $createdScheduledJob);
        $this->assertNotNull($createdScheduledJob['id'], 'Created ScheduledJob must have id specified');
        $this->assertNotNull(ScheduledJob::find($createdScheduledJob['id']), 'ScheduledJob with given id must be in DB');
        $this->assertModelData($scheduledJob, $createdScheduledJob);
    }
    public function test_read_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->create();

        $dbScheduledJob = $this->scheduledJobRepo->find($scheduledJob->id);

        $dbScheduledJob = $dbScheduledJob->toArray();
        $this->assertModelData($scheduledJob->toArray(), $dbScheduledJob);
    }
    public function test_update_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->create();
        $fakeScheduledJob = ScheduledJob::factory()->make()->toArray();

        $updatedScheduledJob = $this->scheduledJobRepo->update($fakeScheduledJob, $scheduledJob->id);

        $this->assertModelData($fakeScheduledJob, $updatedScheduledJob->toArray());
        $dbScheduledJob = $this->scheduledJobRepo->find($scheduledJob->id);
        $this->assertModelData($fakeScheduledJob, $dbScheduledJob->toArray());
    }
    public function test_delete_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->create();

        $resp = $this->scheduledJobRepo->delete($scheduledJob->id);

        $this->assertTrue($resp);
        $this->assertNull(ScheduledJob::find($scheduledJob->id), 'ScheduledJob should not exist in DB');
    }
}
