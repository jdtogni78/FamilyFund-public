<?php

namespace Tests\Feature;

use App\Models\OperationLog;
use App\Models\TransactionExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Additional coverage for OperationsController (Phase 1, issue #22).
 *
 * The existing OperationsControllerTest covers index, run-due-jobs, process-pending
 * and the queue-management access checks. This suite fills the genuinely-uncovered
 * branches found via clover: validatePortfolioBalances (entirely untested), the
 * queue worker PID helpers + start/stop happy/idle paths, and the
 * "skip future-dated" branch of processPending.
 */
class OperationsControllerAdditionalTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $adminUser;
    protected User $regularUser;
    protected string $pidFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();
        $this->regularUser = $this->df->user;

        // Admin is user ID 1 or an address in ADMIN_EMAILS (default admin@dev.familyfund.local).
        $userOne = User::find(1);
        $this->adminUser = $userOne ?: User::factory()->create(['email' => 'admin@dev.familyfund.local']);

        // Same path the controller derives in its constructor.
        $this->pidFile = storage_path('app/queue_worker.pid');

        Mail::fake();
    }

    protected function tearDown(): void
    {
        // The PID file lives on disk (not the DB), so DatabaseTransactions won't
        // roll it back. Clean it up so it can't leak into other tests' index().
        if (is_string($this->pidFile) && file_exists($this->pidFile)) {
            @unlink($this->pidFile);
        }
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== validatePortfolioBalances ====================

    public function test_validate_portfolio_balances_denies_non_admin()
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('operations.validate_portfolio_balances'));

        $response->assertStatus(403);
    }

    public function test_validate_portfolio_balances_runs_for_admin_and_logs()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('operations.validate_portfolio_balances'));

        $response->assertRedirect(route('operations.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('operation_logs', [
            'operation' => 'VALIDATE_PORTFOLIO_BALANCES',
        ]);
    }

    public function test_validate_portfolio_balances_reports_empty_for_date_without_balances()
    {
        // A date far in the past has no portfolio balances -> "no portfolios" info path.
        $response = $this->actingAs($this->adminUser)
            ->get(route('operations.validate_portfolio_balances', ['as_of' => '1990-01-01']));

        $response->assertRedirect(route('operations.index'));
        $response->assertSessionHas('flash_notification');
        $this->assertDatabaseHas('operation_logs', [
            'operation' => 'VALIDATE_PORTFOLIO_BALANCES',
        ]);
    }

    public function test_validate_portfolio_balances_returns_json_when_requested()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('operations.validate_portfolio_balances', ['as_of' => '1990-01-01']));

        $response->assertStatus(200);
        $response->assertJsonStructure(['portfolios', 'has_errors']);
    }

    public function test_validate_portfolio_balances_accepts_custom_threshold()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('operations.validate_portfolio_balances', [
                'as_of' => '1990-01-01',
                'threshold' => 10,
            ]));

        $response->assertRedirect(route('operations.index'));
    }

    // ==================== Queue worker: start / stop / PID helpers ====================

    public function test_start_queue_warns_when_worker_already_running()
    {
        // Writing our own PID makes posix_kill($pid, 0) succeed, so the controller
        // believes a worker is already running and short-circuits (no shell_exec).
        file_put_contents($this->pidFile, (string) getmypid());

        $response = $this->actingAs($this->adminUser)->post(route('operations.queue_start'));

        $response->assertRedirect(route('operations.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_stop_queue_warns_when_no_pid_file()
    {
        // Ensure there is no PID file -> getQueueWorkerPid() returns null.
        if (file_exists($this->pidFile)) {
            @unlink($this->pidFile);
        }

        $response = $this->actingAs($this->adminUser)->post(route('operations.queue_stop'));

        $response->assertRedirect(route('operations.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_stop_queue_warns_when_pid_file_holds_dead_process()
    {
        // A PID that is virtually guaranteed not to exist -> getQueueWorkerPid()
        // returns it, posix_kill(SIGTERM) fails, controller logs the failed stop.
        file_put_contents($this->pidFile, '2147483646');

        $response = $this->actingAs($this->adminUser)->post(route('operations.queue_stop'));

        $response->assertRedirect(route('operations.index'));
        $response->assertSessionHas('flash_notification');
        $this->assertDatabaseHas('operation_logs', [
            'operation' => OperationLog::OP_QUEUE_STOP,
        ]);
    }

    public function test_index_reports_running_worker_when_pid_is_live()
    {
        // Live PID -> isQueueWorkerRunning() exercises the posix_kill($pid, 0)
        // "process exists" branch and getQueueWorkerPid()'s read path.
        file_put_contents($this->pidFile, (string) getmypid());

        $response = $this->actingAs($this->adminUser)->get(route('operations.index'));

        $response->assertStatus(200);
        $response->assertViewHas('queueRunning', true);
    }

    // ==================== processPending: skip future-dated ====================

    public function test_process_pending_skips_future_dated_transaction()
    {
        // Future-dated pending transaction: processPending() returns a non-cleared
        // result, so the controller rolls back and counts it as skipped.
        $this->df->createTransaction(
            100,
            null,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            now()->addWeek()->format('Y-m-d')
        );

        $response = $this->actingAs($this->adminUser)->post(route('operations.process_pending'));

        $response->assertRedirect(route('operations.index'));
        $this->assertDatabaseHas('operation_logs', [
            'operation' => OperationLog::OP_PROCESS_PENDING,
        ]);
        // The future-dated transaction must remain pending.
        $this->assertDatabaseHas('transactions', [
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
        ]);
    }
}
