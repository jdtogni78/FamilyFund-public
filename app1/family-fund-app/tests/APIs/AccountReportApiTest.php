<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\AccountReport;

class AccountReportApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_account_report()
    {
        $accountReport = AccountReport::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/account_reports', $accountReport
        );

        $this->assertApiResponse($accountReport);
    }

    /**
     * @test
     */
    public function test_read_account_report()
    {
        $accountReport = AccountReport::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/account_reports/'.$accountReport->id
        );

        $this->assertApiResponse($accountReport->toArray());
    }

    /**
     * @test
     */
    public function test_update_account_report()
    {
        $accountReport = AccountReport::factory()->create();
        $editedAccountReport = AccountReport::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/account_reports/'.$accountReport->id,
            $editedAccountReport
        );

        $this->assertApiResponse($editedAccountReport);
    }

    /**
     * @test
     */
    public function test_delete_account_report()
    {
        $accountReport = AccountReport::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/account_reports/'.$accountReport->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/account_reports/'.$accountReport->id
        );

        $this->response->assertStatus(404);
    }
}
