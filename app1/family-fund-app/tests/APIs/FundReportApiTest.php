<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\FundReport;

class FundReportApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_fund_report()
    {
        $fundReport = FundReport::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/fund_reports', $fundReport
        );

        $this->assertApiResponse($fundReport);
    }

    /**
     * @test
     */
    public function test_read_fund_report()
    {
        $fundReport = FundReport::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/fund_reports/'.$fundReport->id
        );

        $this->assertApiResponse($fundReport->toArray());
    }

    /**
     * @test
     */
    public function test_update_fund_report()
    {
        $fundReport = FundReport::factory()->create();
        $editedFundReport = FundReport::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/fund_reports/'.$fundReport->id,
            $editedFundReport
        );

        $this->assertApiResponse($editedFundReport);
    }

    /**
     * @test
     */
    public function test_delete_fund_report()
    {
        $fundReport = FundReport::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/fund_reports/'.$fundReport->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/fund_reports/'.$fundReport->id
        );

        $this->response->assertStatus(404);
    }
}
