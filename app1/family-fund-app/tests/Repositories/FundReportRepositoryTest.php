<?php namespace Tests\Repositories;

use App\Models\FundReport;
use App\Repositories\FundReportRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class FundReportRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var FundReportRepository
     */
    protected $fundReportRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->fundReportRepo = \App::make(FundReportRepository::class);
    }
    public function test_create_fund_report()
    {
        $fundReport = FundReport::factory()->make()->toArray();

        $createdFundReport = $this->fundReportRepo->create($fundReport);

        $createdFundReport = $createdFundReport->toArray();
        $this->assertArrayHasKey('id', $createdFundReport);
        $this->assertNotNull($createdFundReport['id'], 'Created FundReport must have id specified');
        $this->assertNotNull(FundReport::find($createdFundReport['id']), 'FundReport with given id must be in DB');
        $this->assertModelData($fundReport, $createdFundReport);
    }
    public function test_read_fund_report()
    {
        $fundReport = FundReport::factory()->create();

        $dbFundReport = $this->fundReportRepo->find($fundReport->id);

        $dbFundReport = $dbFundReport->toArray();
        $this->assertModelData($fundReport->toArray(), $dbFundReport);
    }
    public function test_update_fund_report()
    {
        $fundReport = FundReport::factory()->create();
        $fakeFundReport = FundReport::factory()->make()->toArray();

        $updatedFundReport = $this->fundReportRepo->update($fakeFundReport, $fundReport->id);

        $this->assertModelData($fakeFundReport, $updatedFundReport->toArray());
        $dbFundReport = $this->fundReportRepo->find($fundReport->id);
        $this->assertModelData($fakeFundReport, $dbFundReport->toArray());
    }
    public function test_delete_fund_report()
    {
        $fundReport = FundReport::factory()->create();

        $resp = $this->fundReportRepo->delete($fundReport->id);

        $this->assertTrue($resp);
        $this->assertNull(FundReport::find($fundReport->id), 'FundReport should not exist in DB');
    }
}
