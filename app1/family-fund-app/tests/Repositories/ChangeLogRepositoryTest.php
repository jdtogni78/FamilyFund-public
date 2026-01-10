<?php namespace Tests\Repositories;

use App\Models\ChangeLog;
use App\Repositories\ChangeLogRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

use PHPUnit\Framework\Attributes\Test;
class ChangeLogRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var ChangeLogRepository
     */
    protected $changeLogRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->changeLogRepo = \App::make(ChangeLogRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_change_log()
    {
        $changeLog = ChangeLog::factory()->make()->toArray();

        $createdChangeLog = $this->changeLogRepo->create($changeLog);

        $createdChangeLog = $createdChangeLog->toArray();
        $this->assertArrayHasKey('id', $createdChangeLog);
        $this->assertNotNull($createdChangeLog['id'], 'Created ChangeLog must have id specified');
        $this->assertNotNull(ChangeLog::find($createdChangeLog['id']), 'ChangeLog with given id must be in DB');
        $this->assertModelData($changeLog, $createdChangeLog);
    }

    /**
     * @test read
     */
    public function test_read_change_log()
    {
        $changeLog = ChangeLog::factory()->create();

        $dbChangeLog = $this->changeLogRepo->find($changeLog->id);

        $dbChangeLog = $dbChangeLog->toArray();
        $this->assertModelData($changeLog->toArray(), $dbChangeLog);
    }

    /**
     * @test update
     */
    public function test_update_change_log()
    {
        $changeLog = ChangeLog::factory()->create();
        $fakeChangeLog = ChangeLog::factory()->make()->toArray();

        $updatedChangeLog = $this->changeLogRepo->update($fakeChangeLog, $changeLog->id);

        $this->assertModelData($fakeChangeLog, $updatedChangeLog->toArray());
        $dbChangeLog = $this->changeLogRepo->find($changeLog->id);
        $this->assertModelData($fakeChangeLog, $dbChangeLog->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_change_log()
    {
        $changeLog = ChangeLog::factory()->create();

        $resp = $this->changeLogRepo->delete($changeLog->id);

        $this->assertTrue($resp);
        $this->assertNull(ChangeLog::find($changeLog->id), 'ChangeLog should not exist in DB');
    }
}
