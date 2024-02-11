<?php namespace Tests\Repositories;

use App\Models\ReportSchedule;
use App\Repositories\ReportScheduleRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class ReportScheduleRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var ReportScheduleRepository
     */
    protected $reportScheduleRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->reportScheduleRepo = \App::make(ReportScheduleRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_report_schedule()
    {
        $reportSchedule = ReportSchedule::factory()->make()->toArray();

        $createdReportSchedule = $this->reportScheduleRepo->create($reportSchedule);

        $createdReportSchedule = $createdReportSchedule->toArray();
        $this->assertArrayHasKey('id', $createdReportSchedule);
        $this->assertNotNull($createdReportSchedule['id'], 'Created ReportSchedule must have id specified');
        $this->assertNotNull(ReportSchedule::find($createdReportSchedule['id']), 'ReportSchedule with given id must be in DB');
        $this->assertModelData($reportSchedule, $createdReportSchedule);
    }

    /**
     * @test read
     */
    public function test_read_report_schedule()
    {
        $reportSchedule = ReportSchedule::factory()->create();

        $dbReportSchedule = $this->reportScheduleRepo->find($reportSchedule->id);

        $dbReportSchedule = $dbReportSchedule->toArray();
        $this->assertModelData($reportSchedule->toArray(), $dbReportSchedule);
    }

    /**
     * @test update
     */
    public function test_update_report_schedule()
    {
        $reportSchedule = ReportSchedule::factory()->create();
        $fakeReportSchedule = ReportSchedule::factory()->make()->toArray();

        $updatedReportSchedule = $this->reportScheduleRepo->update($fakeReportSchedule, $reportSchedule->id);

        $this->assertModelData($fakeReportSchedule, $updatedReportSchedule->toArray());
        $dbReportSchedule = $this->reportScheduleRepo->find($reportSchedule->id);
        $this->assertModelData($fakeReportSchedule, $dbReportSchedule->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_report_schedule()
    {
        $reportSchedule = ReportSchedule::factory()->create();

        $resp = $this->reportScheduleRepo->delete($reportSchedule->id);

        $this->assertTrue($resp);
        $this->assertNull(ReportSchedule::find($reportSchedule->id), 'ReportSchedule should not exist in DB');
    }
}
