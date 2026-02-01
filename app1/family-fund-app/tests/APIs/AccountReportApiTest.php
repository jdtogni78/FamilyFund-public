<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\AccountReport;

use PHPUnit\Framework\Attributes\Test;
class AccountReportApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_index_account_reports()
    {
        // Create multiple account reports
        $accountReport1 = AccountReport::factory()->create();
        $accountReport2 = AccountReport::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/account_reports'
        );

        $this->response->assertStatus(200);
        $this->response->assertJson(['success' => true]);
        $responseData = $this->response->json('data');
        $this->assertIsArray($responseData);
        $this->assertGreaterThanOrEqual(2, count($responseData));
    }

    #[Test]
    public function test_index_account_reports_with_pagination()
    {
        // Create multiple account reports
        AccountReport::factory()->count(5)->create();

        // Test with skip and limit
        $this->response = $this->json(
            'GET',
            '/api/account_reports?skip=1&limit=2'
        );

        $this->response->assertStatus(200);
        $this->response->assertJson(['success' => true]);
        $responseData = $this->response->json('data');
        $this->assertIsArray($responseData);
        $this->assertLessThanOrEqual(2, count($responseData));
    }

    #[Test]
    public function test_create_account_report()
    {
        // Fake the queue to prevent jobs from running during test
        Queue::fake();

        $accountReport = AccountReport::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/account_reports', $accountReport
        );

        $this->assertApiResponse($accountReport);
    }

    #[Test]
    public function test_read_account_report()
    {
        $accountReport = AccountReport::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/account_reports/'.$accountReport->id
        );

        $this->assertApiResponse($accountReport->toArray());
    }

    #[Test]
    public function test_read_account_report_not_found()
    {
        $this->response = $this->json(
            'GET',
            '/api/account_reports/999999'
        );

        $this->response->assertStatus(404);
    }

    #[Test]
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

    #[Test]
    public function test_update_account_report_not_found()
    {
        $editedAccountReport = AccountReport::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/account_reports/999999',
            $editedAccountReport
        );

        $this->response->assertStatus(404);
    }

    #[Test]
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

    #[Test]
    public function test_delete_account_report_not_found()
    {
        $this->response = $this->json(
            'DELETE',
            '/api/account_reports/999999'
        );

        $this->response->assertStatus(404);
    }
}
