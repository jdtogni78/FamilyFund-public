<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\ScheduledJobTrait;
use App\Models\OperationLog;
use App\Models\ScheduledJobExt;
use App\Models\TransactionExt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;

class OperationsController extends AppBaseController
{
    use ScheduledJobTrait;

    private string $queuePidFile;

    public function __construct()
    {
        $this->queuePidFile = storage_path('app/queue_worker.pid');
    }

    /**
     * Check if current user is admin (user ID 1 or in ADMIN_EMAILS env)
     */
    private function isAdmin(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // User ID 1 is always admin
        if ($user->id === 1) return true;

        // Check env for additional admin emails
        $adminEmails = explode(',', env('ADMIN_EMAILS', ''));
        return in_array($user->email, $adminEmails);
    }

    /**
     * Operations dashboard
     */
    public function index()
    {
        if (!$this->isAdmin()) {
            Flash::error('Access denied. Admin only.');
            return redirect('/');
        }

        // Scheduled jobs with status
        $scheduledJobs = ScheduledJobExt::with(['schedule', 'fundReportTemplate.fund', 'tradeBandReportTemplate.fund', 'transactionTemplate.account'])
            ->orderBy('entity_descr')
            ->get()
            ->map(function ($job) {
                $lastRun = $job->lastGeneratedReportDate();
                $shouldRunBy = $job->shouldRunBy(Carbon::now());
                return [
                    'job' => $job,
                    'lastRun' => $lastRun,
                    'shouldRunBy' => $shouldRunBy,
                    'isDue' => $shouldRunBy['shouldRunBy']->lte(Carbon::now()),
                ];
            });

        // Pending counts
        $pendingTransactions = TransactionExt::where('status', TransactionExt::STATUS_PENDING)->count();

        // Failed jobs
        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(20)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return (object)[
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'job_name' => $payload['displayName'] ?? 'Unknown',
                    'failed_at' => $job->failed_at,
                    'exception' => \Str::limit($job->exception, 200),
                ];
            });
        $failedJobsCount = DB::table('failed_jobs')->count();

        // Queue worker status
        $queueRunning = $this->isQueueWorkerRunning();

        // Operation logs
        $operationLogs = OperationLog::with('user')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('operations.index', compact(
            'scheduledJobs',
            'pendingTransactions',
            'failedJobs',
            'failedJobsCount',
            'queueRunning',
            'operationLogs'
        ));
    }

    /**
     * Run all due scheduled jobs
     */
    public function runDueJobs()
    {
        if (!$this->isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $asOf = Carbon::now();
        list($results, $errors) = $this->scheduleDueJobs($asOf);

        $jobCount = count($results);
        $errorCount = count($errors);

        if ($errorCount > 0) {
            $errorMessages = array_map(fn($e) => $e->getMessage(), $errors);
            OperationLog::log(
                OperationLog::OP_RUN_DUE_JOBS,
                OperationLog::RESULT_WARNING,
                "Executed $jobCount job(s), $errorCount error(s)",
                ['errors' => $errorMessages]
            );
            Flash::warning("Executed $jobCount job(s) with $errorCount error(s): " . implode('; ', array_slice($errorMessages, 0, 3)));
        } elseif ($jobCount > 0) {
            OperationLog::log(
                OperationLog::OP_RUN_DUE_JOBS,
                OperationLog::RESULT_SUCCESS,
                "Executed $jobCount job(s)"
            );
            Flash::success("Successfully executed $jobCount scheduled job(s).");
        } else {
            OperationLog::log(
                OperationLog::OP_RUN_DUE_JOBS,
                OperationLog::RESULT_SUCCESS,
                "No jobs due"
            );
            Flash::info("No scheduled jobs are due at this time.");
        }

        return redirect(route('operations.index'));
    }

    /**
     * Process all pending transactions
     */
    public function processPending()
    {
        if (!$this->isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $transactions = TransactionExt::where('status', TransactionExt::STATUS_PENDING)
            ->orderBy('timestamp')
            ->orderBy('id')
            ->get();

        if ($transactions->isEmpty()) {
            OperationLog::log(
                OperationLog::OP_PROCESS_PENDING,
                OperationLog::RESULT_SUCCESS,
                "No pending transactions"
            );
            Flash::info('No pending transactions to process.');
            return redirect(route('operations.index'));
        }

        $processed = 0;
        $skipped = 0;
        $errors = [];

        foreach ($transactions as $transaction) {
            try {
                DB::beginTransaction();
                $result = $transaction->processPending();
                if ($result && $result['transaction']->status == TransactionExt::STATUS_CLEARED) {
                    DB::commit();
                    $processed++;
                } else {
                    DB::rollBack();
                    $skipped++;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "ID {$transaction->id}: " . $e->getMessage();
                Log::error("Error processing pending transaction {$transaction->id}: " . $e->getMessage());
            }
        }

        $message = "Processed: $processed, Skipped: $skipped, Errors: " . count($errors);
        $result = count($errors) > 0 ? OperationLog::RESULT_WARNING : OperationLog::RESULT_SUCCESS;

        OperationLog::log(
            OperationLog::OP_PROCESS_PENDING,
            $result,
            $message,
            count($errors) > 0 ? ['errors' => $errors] : null
        );

        if (count($errors) > 0) {
            Flash::warning($message . ". " . implode('; ', array_slice($errors, 0, 3)));
        } elseif ($skipped > 0) {
            Flash::success("Processed $processed transaction(s). Skipped $skipped (future-dated).");
        } else {
            Flash::success("Successfully processed $processed transaction(s).");
        }

        return redirect(route('operations.index'));
    }

    /**
     * Start queue worker
     */
    public function startQueue()
    {
        if (!$this->isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if ($this->isQueueWorkerRunning()) {
            Flash::warning('Queue worker is already running.');
            return redirect(route('operations.index'));
        }

        // Start queue worker in background
        $command = 'cd ' . base_path() . ' && php artisan queue:work --daemon --sleep=3 --tries=3 > /dev/null 2>&1 & echo $!';
        $pid = shell_exec($command);
        $pid = trim($pid);

        if ($pid && is_numeric($pid)) {
            file_put_contents($this->queuePidFile, $pid);
            OperationLog::log(
                OperationLog::OP_QUEUE_START,
                OperationLog::RESULT_SUCCESS,
                "Queue worker started with PID $pid"
            );
            Flash::success("Queue worker started (PID: $pid).");
        } else {
            OperationLog::log(
                OperationLog::OP_QUEUE_START,
                OperationLog::RESULT_ERROR,
                "Failed to start queue worker"
            );
            Flash::error('Failed to start queue worker.');
        }

        return redirect(route('operations.index'));
    }

    /**
     * Stop queue worker
     */
    public function stopQueue()
    {
        if (!$this->isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $pid = $this->getQueueWorkerPid();

        if (!$pid) {
            Flash::warning('No queue worker PID found.');
            return redirect(route('operations.index'));
        }

        // Send SIGTERM to gracefully stop
        $killed = posix_kill($pid, SIGTERM);

        if ($killed) {
            @unlink($this->queuePidFile);
            OperationLog::log(
                OperationLog::OP_QUEUE_STOP,
                OperationLog::RESULT_SUCCESS,
                "Queue worker stopped (PID: $pid)"
            );
            Flash::success("Queue worker stopped (PID: $pid).");
        } else {
            OperationLog::log(
                OperationLog::OP_QUEUE_STOP,
                OperationLog::RESULT_ERROR,
                "Failed to stop queue worker (PID: $pid)"
            );
            Flash::error("Failed to stop queue worker (PID: $pid).");
        }

        return redirect(route('operations.index'));
    }

    /**
     * Retry a failed job
     */
    public function retryFailedJob($uuid)
    {
        if (!$this->isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        try {
            Artisan::call('queue:retry', ['id' => [$uuid]]);
            OperationLog::log(
                OperationLog::OP_QUEUE_RETRY,
                OperationLog::RESULT_SUCCESS,
                "Retried job $uuid"
            );
            Flash::success("Job $uuid has been pushed back onto the queue.");
        } catch (\Exception $e) {
            OperationLog::log(
                OperationLog::OP_QUEUE_RETRY,
                OperationLog::RESULT_ERROR,
                "Failed to retry job $uuid: " . $e->getMessage()
            );
            Flash::error("Failed to retry job: " . $e->getMessage());
        }

        return redirect(route('operations.index'));
    }

    /**
     * Retry all failed jobs
     */
    public function retryAllFailedJobs()
    {
        if (!$this->isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $count = DB::table('failed_jobs')->count();

        if ($count === 0) {
            Flash::info('No failed jobs to retry.');
            return redirect(route('operations.index'));
        }

        try {
            Artisan::call('queue:retry', ['id' => ['all']]);
            OperationLog::log(
                OperationLog::OP_QUEUE_RETRY_ALL,
                OperationLog::RESULT_SUCCESS,
                "Retried all $count failed job(s)"
            );
            Flash::success("All $count failed job(s) have been pushed back onto the queue.");
        } catch (\Exception $e) {
            OperationLog::log(
                OperationLog::OP_QUEUE_RETRY_ALL,
                OperationLog::RESULT_ERROR,
                "Failed to retry jobs: " . $e->getMessage()
            );
            Flash::error("Failed to retry jobs: " . $e->getMessage());
        }

        return redirect(route('operations.index'));
    }

    /**
     * Flush all failed jobs
     */
    public function flushFailedJobs()
    {
        if (!$this->isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $count = DB::table('failed_jobs')->count();

        if ($count === 0) {
            Flash::info('No failed jobs to flush.');
            return redirect(route('operations.index'));
        }

        try {
            Artisan::call('queue:flush');
            OperationLog::log(
                OperationLog::OP_QUEUE_FLUSH,
                OperationLog::RESULT_SUCCESS,
                "Flushed $count failed job(s)"
            );
            Flash::success("Flushed $count failed job(s).");
        } catch (\Exception $e) {
            OperationLog::log(
                OperationLog::OP_QUEUE_FLUSH,
                OperationLog::RESULT_ERROR,
                "Failed to flush jobs: " . $e->getMessage()
            );
            Flash::error("Failed to flush jobs: " . $e->getMessage());
        }

        return redirect(route('operations.index'));
    }

    /**
     * Check if queue worker is running
     */
    private function isQueueWorkerRunning(): bool
    {
        $pid = $this->getQueueWorkerPid();
        if (!$pid) return false;

        // Check if process exists
        return posix_kill($pid, 0);
    }

    /**
     * Get queue worker PID from file
     */
    private function getQueueWorkerPid(): ?int
    {
        if (!file_exists($this->queuePidFile)) {
            return null;
        }

        $pid = (int) file_get_contents($this->queuePidFile);
        return $pid > 0 ? $pid : null;
    }
}
