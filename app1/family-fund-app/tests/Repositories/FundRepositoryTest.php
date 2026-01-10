<?php namespace Tests\Repositories;

use App\Models\Fund;
use App\Repositories\FundRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

use PHPUnit\Framework\Attributes\Test;
class FundRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var FundRepository
     */
    protected $fundRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->fundRepo = \App::make(FundRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_fund()
    {
        $fund = Fund::factory()->make()->toArray();

        $createdFund = $this->fundRepo->create($fund);

        $createdFund = $createdFund->toArray();
        $this->assertArrayHasKey('id', $createdFund);
        $this->assertNotNull($createdFund['id'], 'Created Fund must have id specified');
        $this->assertNotNull(Fund::find($createdFund['id']), 'Fund with given id must be in DB');
        $this->assertModelData($fund, $createdFund);
    }

    /**
     * @test read
     */
    public function test_read_fund()
    {
        $fund = Fund::factory()->create();

        $dbFund = $this->fundRepo->find($fund->id);

        $dbFund = $dbFund->toArray();
        $this->assertModelData($fund->toArray(), $dbFund);
    }

    /**
     * @test update
     */
    public function test_update_fund()
    {
        $fund = Fund::factory()->create();
        $fakeFund = Fund::factory()->make()->toArray();

        $updatedFund = $this->fundRepo->update($fakeFund, $fund->id);

        $this->assertModelData($fakeFund, $updatedFund->toArray());
        $dbFund = $this->fundRepo->find($fund->id);
        $this->assertModelData($fakeFund, $dbFund->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_fund()
    {
        $fund = Fund::factory()->create();

        $resp = $this->fundRepo->delete($fund->id);

        $this->assertTrue($resp);
        $this->assertNull(Fund::find($fund->id), 'Fund should not exist in DB');
    }
}
