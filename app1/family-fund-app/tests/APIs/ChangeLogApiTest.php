<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\ChangeLog;

use PHPUnit\Framework\Attributes\Test;
class ChangeLogApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_change_log()
    {
        $changeLog = ChangeLog::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/change_logs', $changeLog
        );

        $this->assertApiResponse($changeLog);
    }

    #[Test]
    public function test_read_change_log()
    {
        $changeLog = ChangeLog::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/change_logs/'.$changeLog->id
        );

        $this->assertApiResponse($changeLog->toArray());
    }

    #[Test]
    public function test_update_change_log()
    {
        $changeLog = ChangeLog::factory()->create();
        $editedChangeLog = ChangeLog::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/change_logs/'.$changeLog->id,
            $editedChangeLog
        );

        $this->assertApiResponse($editedChangeLog);
    }

    #[Test]
    public function test_delete_change_log()
    {
        $changeLog = ChangeLog::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/change_logs/'.$changeLog->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/change_logs/'.$changeLog->id
        );

        $this->response->assertStatus(404);
    }
}
