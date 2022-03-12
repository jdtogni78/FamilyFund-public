<?php namespace Tests\Repositories;

use App\Models\SymbolPosition;
use App\Repositories\SymbolPositionRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class SymbolPositionRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var SymbolPositionRepository
     */
    protected $symbolPositionRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->symbolPositionRepo = \App::make(SymbolPositionRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_symbol_position()
    {
        $symbolPosition = SymbolPosition::factory()->make()->toArray();

        $createdSymbolPosition = $this->symbolPositionRepo->create($symbolPosition);

        $createdSymbolPosition = $createdSymbolPosition->toArray();
        $this->assertArrayHasKey('id', $createdSymbolPosition);
        $this->assertNotNull($createdSymbolPosition['id'], 'Created SymbolPosition must have id specified');
        $this->assertNotNull(SymbolPosition::find($createdSymbolPosition['id']), 'SymbolPosition with given id must be in DB');
        $this->assertModelData($symbolPosition, $createdSymbolPosition);
    }

    /**
     * @test read
     */
    public function test_read_symbol_position()
    {
        $symbolPosition = SymbolPosition::factory()->create();

        $dbSymbolPosition = $this->symbolPositionRepo->find($symbolPosition->id);

        $dbSymbolPosition = $dbSymbolPosition->toArray();
        $this->assertModelData($symbolPosition->toArray(), $dbSymbolPosition);
    }

    /**
     * @test update
     */
    public function test_update_symbol_position()
    {
        $symbolPosition = SymbolPosition::factory()->create();
        $fakeSymbolPosition = SymbolPosition::factory()->make()->toArray();

        $updatedSymbolPosition = $this->symbolPositionRepo->update($fakeSymbolPosition, $symbolPosition->id);

        $this->assertModelData($fakeSymbolPosition, $updatedSymbolPosition->toArray());
        $dbSymbolPosition = $this->symbolPositionRepo->find($symbolPosition->id);
        $this->assertModelData($fakeSymbolPosition, $dbSymbolPosition->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_symbol_position()
    {
        $symbolPosition = SymbolPosition::factory()->create();

        $resp = $this->symbolPositionRepo->delete($symbolPosition->id);

        $this->assertTrue($resp);
        $this->assertNull(SymbolPosition::find($symbolPosition->id), 'SymbolPosition should not exist in DB');
    }
}
