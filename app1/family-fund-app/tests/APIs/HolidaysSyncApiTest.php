<?php

namespace Tests\APIs;

use App\Models\HolidaysSyncLog;
use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use App\Models\ScheduleExt;
use App\Models\User;
use App\Services\HolidaySyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * API tests for Holidays Sync functionality
 * Tests the integration between scheduled jobs and holiday synchronization
 */
class HolidaysSyncApiTest extends TestCase
{
    use WithoutMiddleware, DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Mail::fake();
    }

    #[Test]
    public function test_schedule_jobs_api_executes_holidays_sync()
    {
        // Clear any existing scheduled jobs to ensure clean test
        ScheduledJob::query()->delete();

        // Arrange: Create a holiday sync scheduled job
        $schedule = ScheduleExt::create([
            'descr' => 'Monthly - 1st',
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => '1',
        ]);

        $job = ScheduledJob::create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
            'start_dt' => Carbon::now()->subMonth(),
            'end_dt' => Carbon::now()->addYear(),
        ]);

        // Mock the sync service
        // Note: The service syncs 4 years (asOf.year-1 to asOf.year+2)
        // For 2026-01-01, it syncs: 2025, 2026, 2027, 2028
        $this->mock(HolidaySyncService::class, function ($mock) {
            $mock->shouldReceive('syncHolidays')
                ->times(4)
                ->andReturn([
                    'records_added' => 4, // 4 per year = 16 total
                    'source' => 'NYSE.com',
                ]);
        });

        // Act: Call the schedule_jobs API endpoint with entity filter to only run holidays_sync
        $response = $this->actingAs($this->user)->postJson('/api/schedule_jobs', [
            'as_of' => '2026-01-01',
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
        ]);

        // Assert: Successful response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);

        // Verify log was created (4 years × 4 records = 16 total)
        $this->assertDatabaseHas('holidays_sync_logs', [
            'scheduled_job_id' => $job->id,
            'exchange' => 'NYSE',
            'records_synced' => 16,
            'source' => 'NYSE.com',
        ]);
    }

    #[Test]
    public function test_schedule_jobs_api_returns_holidays_sync_in_response()
    {
        // Clear any existing scheduled jobs to ensure clean test
        ScheduledJob::query()->delete();

        // Arrange
        $schedule = ScheduleExt::create([
            'descr' => 'Test Schedule',
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => '1',
        ]);

        $job = ScheduledJob::create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
            'start_dt' => Carbon::now()->subMonth(),
            'end_dt' => Carbon::now()->addYear(),
        ]);

        // Mock syncs 4 years (2025-2028 for asOf=2026-02-01)
        $this->mock(HolidaySyncService::class, function ($mock) {
            $mock->shouldReceive('syncHolidays')
                ->times(4)
                ->andReturn([
                    'records_added' => 3, // 3 per year = 12 total
                    'source' => 'test',
                ]);
        });

        // Act: Use entity filter to only run holidays_sync
        $response = $this->actingAs($this->user)->postJson('/api/schedule_jobs', [
            'as_of' => '2026-02-01',
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
        ]);

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');

        // Should have at least one result
        $this->assertNotEmpty($data);

        // Find the holidays_sync log in the response
        $holidaysSyncLog = collect($data)->first(function ($item) {
            return isset($item['exchange']) && $item['exchange'] === 'NYSE';
        });

        $this->assertNotNull($holidaysSyncLog, 'Holidays sync log not found in response');
        $this->assertEquals(12, $holidaysSyncLog['records_synced']); // 4 years × 3 records
    }

    #[Test]
    public function test_schedule_jobs_api_handles_holidays_sync_failure()
    {
        // Clear any existing scheduled jobs to ensure clean test
        ScheduledJob::query()->delete();

        // Arrange
        $schedule = ScheduleExt::create([
            'descr' => 'Test Schedule',
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => '1',
        ]);

        $job = ScheduledJob::create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
            'start_dt' => Carbon::now()->subMonth(),
            'end_dt' => Carbon::now()->addYear(),
        ]);

        // Mock service to throw exception
        $this->mock(HolidaySyncService::class, function ($mock) {
            $mock->shouldReceive('syncHolidays')
                ->andThrow(new \Exception('API connection failed'));
        });

        // Act: Use entity filter to only run holidays_sync
        $response = $this->actingAs($this->user)->postJson('/api/schedule_jobs', [
            'as_of' => '2026-03-01',
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
        ]);

        // Assert: The trait catches exceptions and continues, creating a log with 0 records
        // The API still returns success (200) but the log shows the failure
        $response->assertStatus(200);

        // Log is created but with 0 records due to failure
        $this->assertDatabaseHas('holidays_sync_logs', [
            'scheduled_job_id' => $job->id,
            'records_synced' => 0,
        ]);
    }

    #[Test]
    public function test_holidays_sync_with_entity_descr_filter()
    {
        // Clear any existing scheduled jobs to ensure clean test
        ScheduledJob::query()->delete();

        // Arrange: Create both fund_report and holidays_sync jobs
        $schedule = ScheduleExt::create([
            'descr' => 'Test Schedule',
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => '1',
        ]);

        $holidaysJob = ScheduledJob::create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
            'start_dt' => Carbon::now()->subMonth(),
            'end_dt' => Carbon::now()->addYear(),
        ]);

        // Mock only holidays sync (syncs 4 years: 2025-2028 for asOf=2026-04-01)
        $this->mock(HolidaySyncService::class, function ($mock) {
            $mock->shouldReceive('syncHolidays')
                ->times(4)
                ->andReturn([
                    'records_added' => 2, // 2 per year = 8 total
                    'source' => 'test',
                ]);
        });

        // Act: Call with entity filter
        $response = $this->actingAs($this->user)->postJson('/api/schedule_jobs', [
            'as_of' => '2026-04-01',
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
        ]);

        // Assert
        $response->assertStatus(200);

        // Only holidays_sync should have executed (4 years × 2 records = 8 total)
        $this->assertDatabaseHas('holidays_sync_logs', [
            'scheduled_job_id' => $holidaysJob->id,
            'records_synced' => 8,
        ]);
    }

    #[Test]
    public function test_scheduled_job_crud_supports_holidays_sync_entity()
    {
        // Test creating a scheduled job with holidays_sync entity type
        $schedule = ScheduleExt::create([
            'descr' => 'Test Schedule',
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => '1',
        ]);

        $jobData = [
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
            'start_dt' => Carbon::now()->toDateString(),
            'end_dt' => Carbon::now()->addYear()->toDateString(),
        ];

        // Act: Create via API
        $response = $this->actingAs($this->user)->postJson('/api/scheduled_jobs', $jobData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('scheduled_jobs', [
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'schedule_id' => $schedule->id,
        ]);
    }

    #[Test]
    public function test_holidays_sync_log_relationship_via_api()
    {
        // Arrange: Create a scheduled job and log
        $schedule = ScheduleExt::create([
            'descr' => 'Test Schedule',
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => '1',
        ]);

        $job = ScheduledJob::create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
            'start_dt' => Carbon::now()->subMonth(),
            'end_dt' => Carbon::now()->addYear(),
        ]);

        $log = HolidaysSyncLog::create([
            'scheduled_job_id' => $job->id,
            'exchange' => 'NYSE',
            'synced_at' => Carbon::now(),
            'records_synced' => 15,
            'source' => 'NYSE.com',
        ]);

        // Act: Fetch the scheduled job
        $response = $this->actingAs($this->user)->getJson("/api/scheduled_jobs/{$job->id}");

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data');

        $this->assertEquals(ScheduledJobExt::ENTITY_HOLIDAYS_SYNC, $responseData['entity_descr']);
    }

    #[Test]
    public function test_artisan_command_can_trigger_holidays_sync()
    {
        // NOTE: This test is commented out because the RunScheduledJobs artisan command
        // makes an HTTP request to the API internally, which requires a running server.
        // To test manually: php artisan schedule_jobs:run --as-of=2026-05-01

        $this->markTestIncomplete('Requires HTTP server - uncomment code below to run manually');

        /* Uncomment when testing with running server:

        $schedule = ScheduleExt::create([
            'descr' => 'Test Schedule',
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => '1',
        ]);

        $job = ScheduledJob::create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
            'start_dt' => Carbon::now()->subMonth(),
            'end_dt' => Carbon::now()->addYear(),
        ]);

        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->expects($this->once())
            ->method('syncHolidays')
            ->willReturn([
                'records_added' => 12,
                'source' => 'test',
            ]);
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act: Run the artisan command
        $this->artisan('schedule_jobs:run', ['--as-of' => '2026-05-01'])
            ->assertExitCode(0);

        // Assert: Log created
        $this->assertDatabaseHas('holidays_sync_logs', [
            'scheduled_job_id' => $job->id,
            'records_synced' => 12,
        ]);
        */
    }
}
