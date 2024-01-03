<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\FundReportSchedule;

class FundReportScheduleApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_fund_report_schedule()
    {
        $fundReportSchedule = FundReportSchedule::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/fund_report_schedules', $fundReportSchedule
        );

        $this->assertApiResponse($fundReportSchedule);
    }

    /**
     * @test
     */
    public function test_read_fund_report_schedule()
    {
        $fundReportSchedule = FundReportSchedule::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/fund_report_schedules/'.$fundReportSchedule->id
        );

        $this->assertApiResponse($fundReportSchedule->toArray());
    }

    /**
     * @test
     */
    public function test_update_fund_report_schedule()
    {
        $fundReportSchedule = FundReportSchedule::factory()->create();
        $editedFundReportSchedule = FundReportSchedule::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/fund_report_schedules/'.$fundReportSchedule->id,
            $editedFundReportSchedule
        );

        $this->assertApiResponse($editedFundReportSchedule);
    }

    /**
     * @test
     */
    public function test_delete_fund_report_schedule()
    {
        $fundReportSchedule = FundReportSchedule::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/fund_report_schedules/'.$fundReportSchedule->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/fund_report_schedules/'.$fundReportSchedule->id
        );

        $this->response->assertStatus(404);
    }
}
