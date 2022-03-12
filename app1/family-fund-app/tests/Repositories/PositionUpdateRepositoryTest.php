<?php namespace Tests\Repositories;

use App\Models\PositionUpdate;
use App\Repositories\PositionUpdateRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class PositionUpdateRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var PositionUpdateRepository
     */
    protected $positionUpdateRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->positionUpdateRepo = \App::make(PositionUpdateRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_position_update()
    {
        $positionUpdate = PositionUpdate::factory()->make()->toArray();

        $createdPositionUpdate = $this->positionUpdateRepo->create($positionUpdate);

        $createdPositionUpdate = $createdPositionUpdate->toArray();
        $this->assertArrayHasKey('id', $createdPositionUpdate);
        $this->assertNotNull($createdPositionUpdate['id'], 'Created PositionUpdate must have id specified');
        $this->assertNotNull(PositionUpdate::find($createdPositionUpdate['id']), 'PositionUpdate with given id must be in DB');
        $this->assertModelData($positionUpdate, $createdPositionUpdate);
    }

    /**
     * @test read
     */
    public function test_read_position_update()
    {
        $positionUpdate = PositionUpdate::factory()->create();

        $dbPositionUpdate = $this->positionUpdateRepo->find($positionUpdate->id);

        $dbPositionUpdate = $dbPositionUpdate->toArray();
        $this->assertModelData($positionUpdate->toArray(), $dbPositionUpdate);
    }

    /**
     * @test update
     */
    public function test_update_position_update()
    {
        $positionUpdate = PositionUpdate::factory()->create();
        $fakePositionUpdate = PositionUpdate::factory()->make()->toArray();

        $updatedPositionUpdate = $this->positionUpdateRepo->update($fakePositionUpdate, $positionUpdate->id);

        $this->assertModelData($fakePositionUpdate, $updatedPositionUpdate->toArray());
        $dbPositionUpdate = $this->positionUpdateRepo->find($positionUpdate->id);
        $this->assertModelData($fakePositionUpdate, $dbPositionUpdate->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_position_update()
    {
        $positionUpdate = PositionUpdate::factory()->create();

        $resp = $this->positionUpdateRepo->delete($positionUpdate->id);

        $this->assertTrue($resp);
        $this->assertNull(PositionUpdate::find($positionUpdate->id), 'PositionUpdate should not exist in DB');
    }
}
