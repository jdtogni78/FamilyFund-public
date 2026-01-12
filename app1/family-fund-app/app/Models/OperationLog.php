<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationLog extends Model
{
    public $fillable = [
        'user_id',
        'operation',
        'details',
        'result',
        'message',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    const RESULT_SUCCESS = 'success';
    const RESULT_WARNING = 'warning';
    const RESULT_ERROR = 'error';

    const OP_RUN_DUE_JOBS = 'run_due_jobs';
    const OP_PROCESS_PENDING = 'process_pending';
    const OP_QUEUE_START = 'queue_start';
    const OP_QUEUE_STOP = 'queue_stop';
    const OP_QUEUE_RETRY = 'queue_retry';
    const OP_QUEUE_RETRY_ALL = 'queue_retry_all';
    const OP_QUEUE_FLUSH = 'queue_flush';
    const OP_QUEUE_JOB_COMPLETED = 'queue_job_completed';
    const OP_QUEUE_JOB_FAILED = 'queue_job_failed';
    const OP_SEND_TEST_EMAIL = 'send_test_email';

    public static array $operationMap = [
        self::OP_RUN_DUE_JOBS => 'Run Due Scheduled Jobs',
        self::OP_PROCESS_PENDING => 'Process Pending Transactions',
        self::OP_QUEUE_START => 'Start Queue Worker',
        self::OP_QUEUE_STOP => 'Stop Queue Worker',
        self::OP_QUEUE_RETRY => 'Retry Failed Job',
        self::OP_QUEUE_RETRY_ALL => 'Retry All Failed Jobs',
        self::OP_QUEUE_FLUSH => 'Flush Failed Jobs',
        self::OP_QUEUE_JOB_COMPLETED => 'Queue Job Completed',
        self::OP_QUEUE_JOB_FAILED => 'Queue Job Failed',
        self::OP_SEND_TEST_EMAIL => 'Send Test Email',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operationName(): string
    {
        return self::$operationMap[$this->operation] ?? $this->operation;
    }

    public static function log(string $operation, string $result, ?string $message = null, ?array $details = null): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'operation' => $operation,
            'result' => $result,
            'message' => $message,
            'details' => $details,
        ]);
    }
}
