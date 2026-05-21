<?php

namespace Tests\Unit\Listeners;

use App\Listeners\LogQueueJobCompletion;
use App\Models\OperationLog;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Mockery;
use Tests\TestCase;

class LogQueueJobCompletionTest extends TestCase
{
    use DatabaseTransactions;
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * Build a fake job whose payload() returns $payload and whose
     * getQueue() returns $queue.
     */
    private function fakeJob(array $payload, string $queue = 'default'): Job
    {
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('payload')->andReturn($payload);
        $job->shouldReceive('getQueue')->andReturn($queue);
        return $job;
    }

    public function test_subscribe_maps_processed_and_failed_events(): void
    {
        $map = (new LogQueueJobCompletion())->subscribe(null);

        $this->assertSame(
            [
                JobProcessed::class => 'handleJobProcessed',
                JobFailed::class    => 'handleJobFailed',
            ],
            $map
        );
    }

    public function test_handle_job_processed_writes_success_log(): void
    {
        $job = $this->fakeJob([
            'displayName' => 'App\\Jobs\\Reports\\GenerateQuarterly',
            'data' => ['command' => '<no-model-here>'],
        ], 'reports');
        $event = new JobProcessed('redis', $job);

        (new LogQueueJobCompletion())->handleJobProcessed($event);

        $log = OperationLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame(OperationLog::OP_QUEUE_JOB_COMPLETED, $log->operation);
        $this->assertSame(OperationLog::RESULT_SUCCESS, $log->result);
        $this->assertSame('Job completed: GenerateQuarterly', $log->message);
        $this->assertSame('reports', $log->details['queue']);
        $this->assertSame('redis', $log->details['connection']);
        $this->assertNull($log->details['model_class']);
        $this->assertNull($log->details['model_id']);
    }

    public function test_handle_job_processed_extracts_model_class_and_id(): void
    {
        // Serialized ModelIdentifier shape the regex matches.
        $command = 'O:8:"App\\Foo":1:{s:5:"model";O:54:"Illuminate\\Contracts\\Database\\ModelIdentifier":3:'
                 . '{s:5:"class";s:18:"App\\Models\\Account";s:2:"id";i:42;s:10:"connection";N;}}';
        $job = $this->fakeJob([
            'displayName' => 'App\\Jobs\\Reports\\SendReport',
            'data'        => ['command' => $command],
        ]);

        (new LogQueueJobCompletion())->handleJobProcessed(new JobProcessed('sync', $job));

        $log = OperationLog::latest('id')->first();
        $this->assertSame('App\\Models\\Account', $log->details['model_class']);
        $this->assertSame(42, $log->details['model_id']);
    }

    public function test_handle_job_failed_writes_error_log_with_exception(): void
    {
        $job = $this->fakeJob([
            'displayName' => 'App\\Jobs\\Mail\\SendDigest',
            'data'        => ['command' => '<unused>'],
        ], 'mail');
        $event = new JobFailed('database', $job, new \RuntimeException('connection refused'));

        (new LogQueueJobCompletion())->handleJobFailed($event);

        $log = OperationLog::latest('id')->first();
        $this->assertSame(OperationLog::OP_QUEUE_JOB_FAILED, $log->operation);
        $this->assertSame(OperationLog::RESULT_ERROR, $log->result);
        $this->assertSame('Job failed: SendDigest', $log->message);
        $this->assertSame('connection refused', $log->details['exception']);
        $this->assertSame('mail', $log->details['queue']);
        $this->assertSame('database', $log->details['connection']);
    }

    public function test_handle_job_processed_falls_back_to_unknown_display_name(): void
    {
        $job = $this->fakeJob(['data' => ['command' => '<x>']]);

        (new LogQueueJobCompletion())->handleJobProcessed(new JobProcessed('sync', $job));

        $log = OperationLog::latest('id')->first();
        $this->assertSame('Job completed: Unknown', $log->message);
    }
}
