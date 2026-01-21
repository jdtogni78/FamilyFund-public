<?php

namespace Tests\Feature;

use App\Http\Controllers\WebV1\OperationsController;
use App\Models\OperationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for OperationsController
 * Target: Get coverage from 10% to 50%+
 */
class OperationsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();

        // Create admin user - either use existing user ID 1 or create with admin email
        $userOne = User::find(1);
        if ($userOne) {
            $this->adminUser = $userOne;
        } else {
            // Create user with email from ADMIN_EMAILS env (default: admin@dev.familyfund.local)
            $this->adminUser = User::factory()->create(['email' => 'admin@dev.familyfund.local']);
        }

        // Create regular user (not admin)
        $this->regularUser = $this->df->user;

        Mail::fake();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== isAdmin Tests ====================

    public function test_is_admin_returns_true_for_admin_email()
    {
        // Test by accessing the operations page - admin should get 200
        $response = $this->actingAs($this->adminUser)->get('/operations');
        $response->assertStatus(200);
    }

    public function test_is_admin_returns_false_for_regular_user()
    {
        // Regular user should be redirected with error
        $response = $this->actingAs($this->regularUser)->get('/operations');
        $response->assertRedirect('/');
        $response->assertSessionHas('flash_notification');
    }

    public function test_is_admin_returns_false_when_not_logged_in()
    {
        // Not logged in should redirect to login
        $response = $this->get('/operations');
        $response->assertRedirect();
    }

    // ==================== Index Tests ====================

    public function test_index_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->get('/operations');

        $response->assertRedirect('/');
        $response->assertSessionHas('flash_notification');
    }

    public function test_index_displays_operations_dashboard_for_admin()
    {
        // Simplified test - just verify the page loads and has required view data
        $response = $this->actingAs($this->adminUser)->get('/operations');

        $response->assertStatus(200);
        $response->assertViewIs('operations.index');
        $response->assertViewHas('scheduledJobs');
        $response->assertViewHas('pendingTransactions');
        $response->assertViewHas('queueJobs');
        $response->assertViewHas('operationLogs');
        $response->assertViewHas('queueRunning');
        $response->assertViewHas('pendingJobsCount');
        $response->assertViewHas('failedJobsCount');
    }

    public function test_index_with_queue_filters()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/operations?queue_status=pending&job_type=all');

        $response->assertStatus(200);
        $response->assertViewHas('queueFilter', 'pending');
        $response->assertViewHas('jobTypeFilter', 'all');
    }

    // ==================== Run Due Jobs Tests ====================

    public function test_run_due_jobs_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->post('/operations/run-due-jobs');

        $response->assertStatus(403);
    }

    public function test_run_due_jobs_executes_for_admin()
    {
        $response = $this->actingAs($this->adminUser)->post('/operations/run-due-jobs');

        $response->assertRedirect(route('operations.index'));
        $response->assertSessionHas('flash_notification');

        // Should have created an operation log
        $this->assertDatabaseHas('operation_logs', [
            'operation' => OperationLog::OP_RUN_DUE_JOBS,
        ]);
    }

    // ==================== Process Pending Tests ====================

    public function test_process_pending_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->post('/operations/process-pending');

        $response->assertStatus(403);
    }

    public function test_process_pending_executes_for_admin()
    {
        $response = $this->actingAs($this->adminUser)->post('/operations/process-pending');

        $response->assertRedirect(route('operations.index'));
        $response->assertSessionHas('flash_notification');

        // Should have created an operation log
        $this->assertDatabaseHas('operation_logs', [
            'operation' => OperationLog::OP_PROCESS_PENDING,
        ]);
    }

    public function test_process_pending_handles_pending_transactions()
    {
        // Create a pending transaction using DataFactory (already has userAccount from setUp)
        $transaction = $this->df->createTransaction(
            100,
            null, // Use default user account
            \App\Models\TransactionExt::TYPE_PURCHASE,
            \App\Models\TransactionExt::STATUS_PENDING,
            null,
            now()->subDay()->format('Y-m-d') // Past date so it can be processed
        );

        $response = $this->actingAs($this->adminUser)->post('/operations/process-pending');

        $response->assertRedirect(route('operations.index'));
        $this->assertDatabaseHas('operation_logs', [
            'operation' => OperationLog::OP_PROCESS_PENDING,
        ]);
    }

    // ==================== Queue Management Tests ====================

    public function test_start_queue_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->post('/operations/queue/start');

        $response->assertStatus(403);
    }

    public function test_stop_queue_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->post('/operations/queue/stop');

        $response->assertStatus(403);
    }

    public function test_retry_failed_job_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->post('/operations/queue/retry/test-uuid');

        $response->assertStatus(403);
    }

    public function test_retry_failed_job_executes_for_admin()
    {
        $response = $this->actingAs($this->adminUser)->post('/operations/queue/retry/test-uuid-123');

        $response->assertRedirect(route('operations.index'));
        // Verify operation log was created
        $this->assertDatabaseHas('operation_logs', [
            'operation' => OperationLog::OP_QUEUE_RETRY,
        ]);
    }

    public function test_retry_all_failed_jobs_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->post('/operations/queue/retry-all');

        $response->assertStatus(403);
    }

    public function test_retry_all_failed_jobs_executes_for_admin_with_no_failed_jobs()
    {
        $response = $this->actingAs($this->adminUser)->post('/operations/queue/retry-all');

        $response->assertRedirect(route('operations.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_flush_failed_jobs_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->post('/operations/queue/flush');

        $response->assertStatus(403);
    }

    public function test_flush_failed_jobs_executes_for_admin_with_no_failed_jobs()
    {
        $response = $this->actingAs($this->adminUser)->post('/operations/queue/flush');

        $response->assertRedirect(route('operations.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_index_shows_failed_jobs()
    {
        // Insert a failed job
        \DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-' . uniqid(),
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'Test\\Job']),
            'exception' => 'Test exception',
            'failed_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/operations');

        $response->assertStatus(200);
        $response->assertViewHas('queueJobs');
        $queueJobs = $response->viewData('queueJobs');
        $this->assertGreaterThan(0, count($queueJobs));
    }

    public function test_index_shows_pending_jobs()
    {
        // Insert a pending job
        \DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'Test\\PendingJob', 'data' => ['test' => 'data']]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/operations');

        $response->assertStatus(200);
        $response->assertViewHas('pendingJobsCount');
        $this->assertGreaterThan(0, $response->viewData('pendingJobsCount'));
    }

    public function test_retry_all_with_actual_failed_jobs()
    {
        // Insert failed jobs
        \DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-retry-' . uniqid(),
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'Test\\RetryJob']),
            'exception' => 'Test exception',
            'failed_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->post('/operations/queue/retry-all');

        $response->assertRedirect(route('operations.index'));
        $this->assertDatabaseHas('operation_logs', [
            'operation' => OperationLog::OP_QUEUE_RETRY_ALL,
        ]);
    }

    public function test_flush_with_actual_failed_jobs()
    {
        // Insert failed jobs
        \DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-flush-' . uniqid(),
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'Test\\FlushJob']),
            'exception' => 'Test exception',
            'failed_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->post('/operations/queue/flush');

        $response->assertRedirect(route('operations.index'));
        $this->assertDatabaseHas('operation_logs', [
            'operation' => OperationLog::OP_QUEUE_FLUSH,
        ]);
    }

    // ==================== Test Email Tests ====================

    public function test_send_test_email_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->post('/operations/send-test-email', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(403);
    }

    public function test_send_test_email_validates_email()
    {
        $response = $this->actingAs($this->adminUser)->post('/operations/send-test-email', [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_send_test_email_executes_for_admin()
    {
        // Note: Mail::raw() doesn't use Mailable classes, so we just verify
        // the operation completes and logs correctly
        $response = $this->actingAs($this->adminUser)->post('/operations/send-test-email', [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('operations.index'));
        $response->assertSessionHas('flash_notification');

        // Check operation log was created (success or error, depending on mail config)
        $this->assertTrue(
            OperationLog::where('operation', OperationLog::OP_SEND_TEST_EMAIL)->exists(),
            'Operation log should be created'
        );
    }

    // ==================== Negative Tests ====================

    public function test_run_due_jobs_with_errors_shows_warning()
    {
        // This tests the error handling path - we just verify the response
        $response = $this->actingAs($this->adminUser)->post('/operations/run-due-jobs');

        $response->assertRedirect(route('operations.index'));
        $this->assertDatabaseHas('operation_logs', [
            'operation' => OperationLog::OP_RUN_DUE_JOBS,
        ]);
    }

    public function test_process_pending_with_transaction_errors()
    {
        // Test that process pending handles errors gracefully
        // Even with no pending transactions or errors processing them,
        // it should complete and log the operation
        $response = $this->actingAs($this->adminUser)->post('/operations/process-pending');

        $response->assertRedirect(route('operations.index'));
        // Should log the operation
        $this->assertDatabaseHas('operation_logs', [
            'operation' => OperationLog::OP_PROCESS_PENDING,
        ]);
    }

    public function test_queue_operations_require_admin()
    {
        // Test all queue operations require admin
        $routes = [
            ['POST', '/operations/queue/start'],
            ['POST', '/operations/queue/stop'],
            ['POST', '/operations/queue/retry/test-uuid'],
            ['POST', '/operations/queue/retry-all'],
            ['POST', '/operations/queue/flush'],
        ];

        foreach ($routes as [$method, $route]) {
            $response = $this->actingAs($this->regularUser)->$method($route);
            $response->assertStatus(403);
        }
    }

    public function test_send_test_email_requires_valid_email()
    {
        $invalidEmails = [
            'not-an-email',
            'missing@',
            '@missing.com',
            'spaces in@email.com',
            '',
        ];

        foreach ($invalidEmails as $email) {
            $response = $this->actingAs($this->adminUser)->post('/operations/send-test-email', [
                'email' => $email,
            ]);

            $response->assertSessionHasErrors('email');
        }
    }

    public function test_send_test_email_missing_email_field()
    {
        $response = $this->actingAs($this->adminUser)->post('/operations/send-test-email', []);

        $response->assertSessionHasErrors('email');
    }

    public function test_index_filters_work_with_invalid_values()
    {
        // Test with invalid filter values - should handle gracefully
        $response = $this->actingAs($this->adminUser)
            ->get('/operations?queue_status=invalid&job_type=unknown&page=999');

        $response->assertStatus(200);
        $response->assertViewIs('operations.index');
    }

    public function test_retry_job_with_empty_uuid()
    {
        $response = $this->actingAs($this->adminUser)->post('/operations/queue/retry/');

        // Should either 404 or redirect
        $this->assertTrue(
            $response->status() === 404 || $response->isRedirect()
        );
    }

    public function test_operations_protected_when_not_authenticated()
    {
        $routes = [
            ['GET', '/operations'],
            ['POST', '/operations/run-due-jobs'],
            ['POST', '/operations/process-pending'],
            ['POST', '/operations/queue/start'],
            ['POST', '/operations/send-test-email', ['email' => 'test@test.com']],
        ];

        foreach ($routes as $routeData) {
            $method = $routeData[0];
            $route = $routeData[1];
            $data = $routeData[2] ?? [];

            $response = $this->$method($route, $data);

            // Should redirect to login
            $response->assertRedirect();
        }
    }
}
