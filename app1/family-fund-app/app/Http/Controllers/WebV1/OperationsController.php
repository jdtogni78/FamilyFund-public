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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
    public static function isAdmin(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // User ID 1 is always admin
        if ($user->id === 1) return true;

        // Check env for additional admin emails (default includes app owner)
        $defaultAdmins = 'admin@dev.familyfund.local';
        $adminEmails = explode(',', env('ADMIN_EMAILS', $defaultAdmins));
        return in_array($user->email, array_map('trim', $adminEmails));
    }

    /**
     * Operations dashboard
     */
    public function index(Request $request)
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

        // Queue jobs with filters
        $queueFilter = $request->get('queue_status', 'all');
        $jobTypeFilter = $request->get('job_type', 'all');
        $perPage = 15;

        // Get all queue jobs (pending + failed)
        $queueJobsData = $this->getQueueJobs($queueFilter, $jobTypeFilter, $perPage, $request->get('page', 1));
        $queueJobs = $queueJobsData['jobs'];
        $queueJobsPaginator = $queueJobsData['paginator'];

        // Get unique job types for filter dropdown
        $jobTypes = $this->getJobTypes();

        // Counts for badges
        $pendingJobsCount = DB::table('jobs')->count();
        $failedJobsCount = DB::table('failed_jobs')->count();

        // Queue worker status
        $queueRunning = $this->isQueueWorkerRunning();

        // Operation logs
        $operationLogs = OperationLog::with('user')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Email configuration
        $emailConfig = $this->getEmailConfig();

        // Email logs
        $emailLogs = $this->getRecentEmailLogs(25);

        return view('operations.index', compact(
            'scheduledJobs',
            'pendingTransactions',
            'queueJobs',
            'queueJobsPaginator',
            'jobTypes',
            'queueFilter',
            'jobTypeFilter',
            'pendingJobsCount',
            'failedJobsCount',
            'queueRunning',
            'operationLogs',
            'emailConfig',
            'emailLogs'
        ));
    }

    /**
     * Get queue jobs with filters and pagination
     */
    private function getQueueJobs(string $statusFilter, string $typeFilter, int $perPage, int $page): array
    {
        $allJobs = collect();

        // Get pending jobs
        if ($statusFilter === 'all' || $statusFilter === 'pending') {
            $pendingJobs = DB::table('jobs')->get()->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return (object)[
                    'id' => $job->id,
                    'uuid' => null,
                    'job_name' => $payload['displayName'] ?? 'Unknown',
                    'status' => 'pending',
                    'queue' => $job->queue,
                    'attempts' => $job->attempts,
                    'created_at' => Carbon::createFromTimestamp($job->created_at),
                    'failed_at' => null,
                    'exception' => null,
                ];
            });
            $allJobs = $allJobs->concat($pendingJobs);
        }

        // Get failed jobs
        if ($statusFilter === 'all' || $statusFilter === 'failed') {
            $failedJobs = DB::table('failed_jobs')->get()->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return (object)[
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'job_name' => $payload['displayName'] ?? 'Unknown',
                    'status' => 'failed',
                    'queue' => $job->queue,
                    'attempts' => null,
                    'created_at' => null,
                    'failed_at' => Carbon::parse($job->failed_at),
                    'exception' => \Str::limit($job->exception, 300),
                ];
            });
            $allJobs = $allJobs->concat($failedJobs);
        }

        // Filter by job type
        if ($typeFilter !== 'all') {
            $allJobs = $allJobs->filter(fn($job) => $job->job_name === $typeFilter);
        }

        // Sort by date (failed_at or created_at, most recent first)
        $allJobs = $allJobs->sortByDesc(fn($job) => $job->failed_at ?? $job->created_at);

        // Paginate
        $total = $allJobs->count();
        $jobs = $allJobs->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $jobs,
            $total,
            $perPage,
            $page,
            ['path' => route('operations.index'), 'query' => request()->query()]
        );

        return ['jobs' => $jobs, 'paginator' => $paginator];
    }

    /**
     * Get unique job types from both tables
     */
    private function getJobTypes(): array
    {
        $types = collect();

        // From pending jobs
        DB::table('jobs')->get()->each(function ($job) use (&$types) {
            $payload = json_decode($job->payload, true);
            $types->push($payload['displayName'] ?? 'Unknown');
        });

        // From failed jobs
        DB::table('failed_jobs')->get()->each(function ($job) use (&$types) {
            $payload = json_decode($job->payload, true);
            $types->push($payload['displayName'] ?? 'Unknown');
        });

        return $types->unique()->sort()->values()->toArray();
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

    /**
     * Get email configuration for display
     */
    private function getEmailConfig(): array
    {
        return [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption') ?: 'none',
            'username' => config('mail.mailers.smtp.username') ?: '(not set)',
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'admin_address' => env('MAIL_ADMIN_ADDRESS', '(not set)'),
        ];
    }

    /**
     * Get recent email logs from storage
     */
    private function getRecentEmailLogs(int $limit = 25): array
    {
        $logs = [];
        $basePath = 'emails';

        try {
            // Get all year directories
            $years = Storage::disk('local')->directories($basePath);
            rsort($years); // Most recent first

            foreach ($years as $yearDir) {
                $months = Storage::disk('local')->directories($yearDir);
                rsort($months);

                foreach ($months as $monthDir) {
                    $files = Storage::disk('local')->files($monthDir);
                    rsort($files); // Most recent files first

                    foreach ($files as $file) {
                        if (count($logs) >= $limit) break 3;

                        $content = Storage::disk('local')->get($file);
                        $data = json_decode($content, true);

                        if ($data) {
                            $logs[] = [
                                'file' => basename($file),
                                'timestamp' => $data['timestamp'] ?? null,
                                'subject' => $data['subject'] ?? '(no subject)',
                                'to' => $this->formatEmailAddresses($data['to'] ?? []),
                                'from' => $this->formatEmailAddresses($data['from'] ?? []),
                                'attachments' => count($data['attachments'] ?? []),
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to read email logs: ' . $e->getMessage());
        }

        return $logs;
    }

    /**
     * Format email addresses for display
     */
    private function formatEmailAddresses(array $addresses): string
    {
        return collect($addresses)
            ->map(fn($a) => $a['email'] ?? '')
            ->filter()
            ->implode(', ');
    }

    /**
     * Send a test email
     */
    public function sendTestEmail(Request $request)
    {
        if (!$this->isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'email' => 'required|email',
        ]);

        $to = $request->input('email');

        try {
            Mail::raw(
                "This is a test email from Family Fund.\n\n" .
                "Environment: " . config('app.env') . "\n" .
                "Sent at: " . now()->format('Y-m-d H:i:s') . "\n" .
                "Mailer: " . config('mail.default') . "\n" .
                "Host: " . config('mail.mailers.smtp.host') . ":" . config('mail.mailers.smtp.port'),
                function ($message) use ($to) {
                    $message->to($to)
                        ->subject('Family Fund - Test Email');
                }
            );

            OperationLog::log(
                OperationLog::OP_SEND_TEST_EMAIL,
                OperationLog::RESULT_SUCCESS,
                "Test email sent to $to"
            );
            Flash::success("Test email sent to $to");
        } catch (\Exception $e) {
            OperationLog::log(
                OperationLog::OP_SEND_TEST_EMAIL,
                OperationLog::RESULT_ERROR,
                "Failed to send test email to $to: " . $e->getMessage()
            );
            Flash::error("Failed to send test email: " . $e->getMessage());
        }

        return redirect(route('operations.index'));
    }
}
