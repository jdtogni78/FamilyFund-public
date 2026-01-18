<?php

namespace Tests\Unit;

use App\Jobs\SendAccountReport;
use App\Jobs\SendFundReport;
use App\Listeners\LogQueueJobCompletion;
use App\Models\AccountReport;
use App\Models\FundReport;
use App\Models\OperationLog;
use Illuminate\Support\Facades\Mail;
use ReflectionMethod;
use Tests\TestCase;

class DuplicateEmailPreventionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    protected function tearDown(): void
    {
        // Clean up test operation logs
        OperationLog::where('message', 'like', '%Test%')->delete();
        parent::tearDown();
    }

    /**
     * Test that LogQueueJobCompletion extracts model info from job payload.
     */
    public function test_extracts_model_info_from_job_payload(): void
    {
        $listener = new LogQueueJobCompletion();
        $method = new ReflectionMethod($listener, 'extractModelInfo');
        $method->setAccessible(true);

        // Simulate a real job payload with serialized model
        $payload = [
            'data' => [
                'command' => 'O:26:"App\Jobs\SendAccountReport":1:{s:41:"' . "\x00" . 'App\Jobs\SendAccountReport' . "\x00" . 'accountReport";O:45:"Illuminate\Contracts\Database\ModelIdentifier":5:{s:5:"class";s:24:"App\Models\AccountReport";s:2:"id";i:123;s:9:"relations";a:0:{}s:10:"connection";s:5:"mysql";s:15:"collectionClass";N;}}'
            ]
        ];

        $result = $method->invoke($listener, $payload);

        $this->assertEquals('App\Models\AccountReport', $result['model_class']);
        $this->assertEquals(123, $result['model_id']);
    }

    /**
     * Test that jobCompletedForModel returns false when no completion record exists.
     */
    public function test_job_completed_for_model_returns_false_when_not_completed(): void
    {
        // Use a very high ID that won't exist
        $result = OperationLog::jobCompletedForModel(
            SendAccountReport::class,
            AccountReport::class,
            999999
        );

        $this->assertFalse($result);
    }

    /**
     * Test that jobCompletedForModel returns true when completion record exists.
     */
    public function test_job_completed_for_model_returns_true_when_completed(): void
    {
        $testModelId = 100000 + rand(1, 9999);

        // Create a completion record
        $log = OperationLog::create([
            'user_id' => null,
            'operation' => OperationLog::OP_QUEUE_JOB_COMPLETED,
            'result' => OperationLog::RESULT_SUCCESS,
            'message' => 'Test: Job completed: SendAccountReport',
            'details' => [
                'job_name' => SendAccountReport::class,
                'model_class' => AccountReport::class,
                'model_id' => $testModelId,
            ],
        ]);

        $result = OperationLog::jobCompletedForModel(
            SendAccountReport::class,
            AccountReport::class,
            $testModelId
        );

        $this->assertTrue($result);

        // Cleanup
        $log->delete();
    }

    /**
     * Test that clearJobCompletionForModel removes completion records.
     */
    public function test_clear_job_completion_allows_resending(): void
    {
        $testModelId = 200000 + rand(1, 9999);

        // Create a completion record
        OperationLog::create([
            'user_id' => null,
            'operation' => OperationLog::OP_QUEUE_JOB_COMPLETED,
            'result' => OperationLog::RESULT_SUCCESS,
            'message' => 'Test: Job completed: SendAccountReport',
            'details' => [
                'job_name' => SendAccountReport::class,
                'model_class' => AccountReport::class,
                'model_id' => $testModelId,
            ],
        ]);

        // Verify it's marked as completed
        $this->assertTrue(OperationLog::jobCompletedForModel(
            SendAccountReport::class,
            AccountReport::class,
            $testModelId
        ));

        // Clear the completion record
        $deleted = OperationLog::clearJobCompletionForModel(AccountReport::class, $testModelId);

        $this->assertEquals(1, $deleted);

        // Verify it's no longer marked as completed
        $this->assertFalse(OperationLog::jobCompletedForModel(
            SendAccountReport::class,
            AccountReport::class,
            $testModelId
        ));
    }

    /**
     * Test that SendAccountReport would skip when job already completed.
     * (Tests the OperationLog check, not actual job execution)
     */
    public function test_send_account_report_skips_when_already_completed(): void
    {
        $testReportId = 400000 + rand(1, 9999);

        // Mark the job as already completed
        $log = OperationLog::create([
            'user_id' => null,
            'operation' => OperationLog::OP_QUEUE_JOB_COMPLETED,
            'result' => OperationLog::RESULT_SUCCESS,
            'message' => 'Test: Job completed: SendAccountReport',
            'details' => [
                'job_name' => SendAccountReport::class,
                'model_class' => AccountReport::class,
                'model_id' => $testReportId,
            ],
        ]);

        // The job would check OperationLog and skip if completed
        $this->assertTrue(OperationLog::jobCompletedForModel(
            SendAccountReport::class,
            AccountReport::class,
            $testReportId
        ));

        // Cleanup
        $log->delete();
    }

    /**
     * Test that SendFundReport would skip when job already completed.
     * (Tests the OperationLog check, not actual job execution)
     */
    public function test_send_fund_report_skips_when_already_completed(): void
    {
        $testReportId = 500000 + rand(1, 9999);

        // Mark the job as already completed
        $log = OperationLog::create([
            'user_id' => null,
            'operation' => OperationLog::OP_QUEUE_JOB_COMPLETED,
            'result' => OperationLog::RESULT_SUCCESS,
            'message' => 'Test: Job completed: SendFundReport',
            'details' => [
                'job_name' => SendFundReport::class,
                'model_class' => FundReport::class,
                'model_id' => $testReportId,
            ],
        ]);

        // The job would check OperationLog and skip if completed
        $this->assertTrue(OperationLog::jobCompletedForModel(
            SendFundReport::class,
            FundReport::class,
            $testReportId
        ));

        // Cleanup
        $log->delete();
    }

    /**
     * Test the full flow: job completes, retry is blocked, cleanup allows resend.
     */
    public function test_full_duplicate_prevention_flow(): void
    {
        $testModelId = 300000 + rand(1, 9999);

        // Step 1: Initially, job is not marked as completed
        $this->assertFalse(OperationLog::jobCompletedForModel(
            SendAccountReport::class,
            AccountReport::class,
            $testModelId
        ));

        // Step 2: Simulate job completion by adding operation log
        OperationLog::create([
            'user_id' => null,
            'operation' => OperationLog::OP_QUEUE_JOB_COMPLETED,
            'result' => OperationLog::RESULT_SUCCESS,
            'message' => 'Test: Job completed: SendAccountReport',
            'details' => [
                'job_name' => SendAccountReport::class,
                'model_class' => AccountReport::class,
                'model_id' => $testModelId,
            ],
        ]);

        // Step 3: Now job is marked as completed - would skip on retry
        $this->assertTrue(OperationLog::jobCompletedForModel(
            SendAccountReport::class,
            AccountReport::class,
            $testModelId
        ));

        // Step 4: Clear for resend
        $deleted = OperationLog::clearJobCompletionForModel(AccountReport::class, $testModelId);
        $this->assertEquals(1, $deleted);

        // Step 5: Job is no longer marked as completed, can resend
        $this->assertFalse(OperationLog::jobCompletedForModel(
            SendAccountReport::class,
            AccountReport::class,
            $testModelId
        ));
    }
}
