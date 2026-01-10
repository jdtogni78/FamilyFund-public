<?php namespace Tests\Repositories;

use App\Models\SymbolPrice;
use App\Repositories\SymbolPriceRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

use PHPUnit\Framework\Attributes\Test;
class SymbolPriceRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var SymbolPriceRepository
     */
    protected $symbolPriceRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->symbolPriceRepo = \App::make(SymbolPriceRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_symbol_price()
    {
        $symbolPrice = SymbolPrice::factory()->make()->toArray();

        $createdSymbolPrice = $this->symbolPriceRepo->create($symbolPrice);

        $createdSymbolPrice = $createdSymbolPrice->toArray();
        $this->assertArrayHasKey('id', $createdSymbolPrice);
        $this->assertNotNull($createdSymbolPrice['id'], 'Created SymbolPrice must have id specified');
        $this->assertNotNull(SymbolPrice::find($createdSymbolPrice['id']), 'SymbolPrice with given id must be in DB');
        $this->assertModelData($symbolPrice, $createdSymbolPrice);
    }

    /**
     * @test read
     */
    public function test_read_symbol_price()
    {
        $symbolPrice = SymbolPrice::factory()->create();

        $dbSymbolPrice = $this->symbolPriceRepo->find($symbolPrice->id);

        $dbSymbolPrice = $dbSymbolPrice->toArray();
        $this->assertModelData($symbolPrice->toArray(), $dbSymbolPrice);
    }

    /**
     * @test update
     */
    public function test_update_symbol_price()
    {
        $symbolPrice = SymbolPrice::factory()->create();
        $fakeSymbolPrice = SymbolPrice::factory()->make()->toArray();

        $updatedSymbolPrice = $this->symbolPriceRepo->update($fakeSymbolPrice, $symbolPrice->id);

        $this->assertModelData($fakeSymbolPrice, $updatedSymbolPrice->toArray());
        $dbSymbolPrice = $this->symbolPriceRepo->find($symbolPrice->id);
        $this->assertModelData($fakeSymbolPrice, $dbSymbolPrice->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_symbol_price()
    {
        $symbolPrice = SymbolPrice::factory()->create();

        $resp = $this->symbolPriceRepo->delete($symbolPrice->id);

        $this->assertTrue($resp);
        $this->assertNull(SymbolPrice::find($symbolPrice->id), 'SymbolPrice should not exist in DB');
    }
}
