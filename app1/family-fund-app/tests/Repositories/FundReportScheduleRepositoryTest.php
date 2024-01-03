<?php namespace Tests\Repositories;

use App\Models\FundReportSchedule;
use App\Repositories\FundReportScheduleRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class FundReportScheduleRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var FundReportScheduleRepository
     */
    protected $fundReportScheduleRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->fundReportScheduleRepo = \App::make(FundReportScheduleRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_fund_report_schedule()
    {
        $fundReportSchedule = FundReportSchedule::factory()->make()->toArray();

        $createdFundReportSchedule = $this->fundReportScheduleRepo->create($fundReportSchedule);

        $createdFundReportSchedule = $createdFundReportSchedule->toArray();
        $this->assertArrayHasKey('id', $createdFundReportSchedule);
        $this->assertNotNull($createdFundReportSchedule['id'], 'Created FundReportSchedule must have id specified');
        $this->assertNotNull(FundReportSchedule::find($createdFundReportSchedule['id']), 'FundReportSchedule with given id must be in DB');
        $this->assertModelData($fundReportSchedule, $createdFundReportSchedule);
    }

    /**
     * @test read
     */
    public function test_read_fund_report_schedule()
    {
        $fundReportSchedule = FundReportSchedule::factory()->create();

        $dbFundReportSchedule = $this->fundReportScheduleRepo->find($fundReportSchedule->id);

        $dbFundReportSchedule = $dbFundReportSchedule->toArray();
        $this->assertModelData($fundReportSchedule->toArray(), $dbFundReportSchedule);
    }

    /**
     * @test update
     */
    public function test_update_fund_report_schedule()
    {
        $fundReportSchedule = FundReportSchedule::factory()->create();
        $fakeFundReportSchedule = FundReportSchedule::factory()->make()->toArray();

        $updatedFundReportSchedule = $this->fundReportScheduleRepo->update($fakeFundReportSchedule, $fundReportSchedule->id);

        $this->assertModelData($fakeFundReportSchedule, $updatedFundReportSchedule->toArray());
        $dbFundReportSchedule = $this->fundReportScheduleRepo->find($fundReportSchedule->id);
        $this->assertModelData($fakeFundReportSchedule, $dbFundReportSchedule->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_fund_report_schedule()
    {
        $fundReportSchedule = FundReportSchedule::factory()->create();

        $resp = $this->fundReportScheduleRepo->delete($fundReportSchedule->id);

        $this->assertTrue($resp);
        $this->assertNull(FundReportSchedule::find($fundReportSchedule->id), 'FundReportSchedule should not exist in DB');
    }
}
