<?php

namespace App\Listeners;

use App\Models\OperationLog;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;

class LogQueueJobCompletion
{
    /**
     * Handle successful job completion.
     */
    public function handleJobProcessed(JobProcessed $event): void
    {
        $payload = $event->job->payload();
        $jobName = $payload['displayName'] ?? 'Unknown';

        OperationLog::create([
            'user_id' => null, // Queue jobs run without user context
            'operation' => OperationLog::OP_QUEUE_JOB_COMPLETED,
            'result' => OperationLog::RESULT_SUCCESS,
            'message' => "Job completed: " . class_basename($jobName),
            'details' => [
                'job_name' => $jobName,
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
            ],
        ]);
    }

    /**
     * Handle failed job.
     */
    public function handleJobFailed(JobFailed $event): void
    {
        $payload = $event->job->payload();
        $jobName = $payload['displayName'] ?? 'Unknown';

        OperationLog::create([
            'user_id' => null,
            'operation' => OperationLog::OP_QUEUE_JOB_FAILED,
            'result' => OperationLog::RESULT_ERROR,
            'message' => "Job failed: " . class_basename($jobName),
            'details' => [
                'job_name' => $jobName,
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'exception' => $event->exception->getMessage(),
            ],
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            JobProcessed::class => 'handleJobProcessed',
            JobFailed::class => 'handleJobFailed',
        ];
    }
}
