<?php

namespace App\Listeners;

use App\Models\OperationLog;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;

class LogQueueJobCompletion
{
    /**
     * Extract model class and ID from serialized job command.
     */
    protected function extractModelInfo(array $payload): array
    {
        $info = ['model_class' => null, 'model_id' => null];

        $command = $payload['data']['command'] ?? null;
        if (!$command) {
            return $info;
        }

        // Extract model class from ModelIdentifier
        if (preg_match('/ModelIdentifier[^}]+s:5:"class";s:\d+:"([^"]+)"/', $command, $matches)) {
            $info['model_class'] = $matches[1];
        }

        // Extract model ID
        if (preg_match('/ModelIdentifier[^}]+s:2:"id";i:(\d+)/', $command, $matches)) {
            $info['model_id'] = (int) $matches[1];
        }

        return $info;
    }

    /**
     * Handle successful job completion.
     */
    public function handleJobProcessed(JobProcessed $event): void
    {
        $payload = $event->job->payload();
        $jobName = $payload['displayName'] ?? 'Unknown';
        $modelInfo = $this->extractModelInfo($payload);

        OperationLog::create([
            'user_id' => null, // Queue jobs run without user context
            'operation' => OperationLog::OP_QUEUE_JOB_COMPLETED,
            'result' => OperationLog::RESULT_SUCCESS,
            'message' => "Job completed: " . class_basename($jobName),
            'details' => [
                'job_name' => $jobName,
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'model_class' => $modelInfo['model_class'],
                'model_id' => $modelInfo['model_id'],
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
        $modelInfo = $this->extractModelInfo($payload);

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
                'model_class' => $modelInfo['model_class'],
                'model_id' => $modelInfo['model_id'],
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
