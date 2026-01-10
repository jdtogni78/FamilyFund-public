<?php namespace Tests\Repositories;

use App\Models\PriceUpdate;
use App\Repositories\PriceUpdateRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

use PHPUnit\Framework\Attributes\Test;
class PriceUpdateRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var PriceUpdateRepository
     */
    protected $priceUpdateRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->priceUpdateRepo = \App::make(PriceUpdateRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_price_update()
    {
        $priceUpdate = PriceUpdate::factory()->make()->toArray();

        $createdPriceUpdate = $this->priceUpdateRepo->create($priceUpdate);

        $createdPriceUpdate = $createdPriceUpdate->toArray();
        $this->assertArrayHasKey('id', $createdPriceUpdate);
        $this->assertNotNull($createdPriceUpdate['id'], 'Created PriceUpdate must have id specified');
        $this->assertNotNull(PriceUpdate::find($createdPriceUpdate['id']), 'PriceUpdate with given id must be in DB');
        $this->assertModelData($priceUpdate, $createdPriceUpdate);
    }

    /**
     * @test read
     */
    public function test_read_price_update()
    {
        $priceUpdate = PriceUpdate::factory()->create();

        $dbPriceUpdate = $this->priceUpdateRepo->find($priceUpdate->id);

        $dbPriceUpdate = $dbPriceUpdate->toArray();
        $this->assertModelData($priceUpdate->toArray(), $dbPriceUpdate);
    }

    /**
     * @test update
     */
    public function test_update_price_update()
    {
        $priceUpdate = PriceUpdate::factory()->create();
        $fakePriceUpdate = PriceUpdate::factory()->make()->toArray();

        $updatedPriceUpdate = $this->priceUpdateRepo->update($fakePriceUpdate, $priceUpdate->id);

        $this->assertModelData($fakePriceUpdate, $updatedPriceUpdate->toArray());
        $dbPriceUpdate = $this->priceUpdateRepo->find($priceUpdate->id);
        $this->assertModelData($fakePriceUpdate, $dbPriceUpdate->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_price_update()
    {
        $priceUpdate = PriceUpdate::factory()->create();

        $resp = $this->priceUpdateRepo->delete($priceUpdate->id);

        $this->assertTrue($resp);
        $this->assertNull(PriceUpdate::find($priceUpdate->id), 'PriceUpdate should not exist in DB');
    }
}
