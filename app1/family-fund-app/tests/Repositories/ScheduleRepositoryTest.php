<?php namespace Tests\Repositories;

use App\Models\Schedule;
use App\Repositories\ScheduleRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

use PHPUnit\Framework\Attributes\Test;
class ScheduleRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var ScheduleRepository
     */
    protected $scheduleRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->scheduleRepo = \App::make(ScheduleRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_schedule()
    {
        $schedule = Schedule::factory()->make()->toArray();

        $createdSchedule = $this->scheduleRepo->create($schedule);

        $createdSchedule = $createdSchedule->toArray();
        $this->assertArrayHasKey('id', $createdSchedule);
        $this->assertNotNull($createdSchedule['id'], 'Created Schedule must have id specified');
        $this->assertNotNull(Schedule::find($createdSchedule['id']), 'Schedule with given id must be in DB');
        $this->assertModelData($schedule, $createdSchedule);
    }

    /**
     * @test read
     */
    public function test_read_schedule()
    {
        $schedule = Schedule::factory()->create();

        $dbSchedule = $this->scheduleRepo->find($schedule->id);

        $dbSchedule = $dbSchedule->toArray();
        $this->assertModelData($schedule->toArray(), $dbSchedule);
    }

    /**
     * @test update
     */
    public function test_update_schedule()
    {
        $schedule = Schedule::factory()->create();
        $fakeSchedule = Schedule::factory()->make()->toArray();

        $updatedSchedule = $this->scheduleRepo->update($fakeSchedule, $schedule->id);

        $this->assertModelData($fakeSchedule, $updatedSchedule->toArray());
        $dbSchedule = $this->scheduleRepo->find($schedule->id);
        $this->assertModelData($fakeSchedule, $dbSchedule->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_schedule()
    {
        $schedule = Schedule::factory()->create();

        $resp = $this->scheduleRepo->delete($schedule->id);

        $this->assertTrue($resp);
        $this->assertNull(Schedule::find($schedule->id), 'Schedule should not exist in DB');
    }
}
