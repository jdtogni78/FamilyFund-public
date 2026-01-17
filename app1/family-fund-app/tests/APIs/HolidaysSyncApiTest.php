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
    }

    #[Test]
    public function test_schedule_jobs_api_executes_holidays_sync()
    {
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
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->expects($this->once())
            ->method('syncHolidays')
            ->with('NYSE', 2026)
            ->willReturn([
                'records_added' => 15,
                'source' => 'NYSE.com',
            ]);
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act: Call the schedule_jobs API endpoint
        $response = $this->actingAs($this->user)->postJson('/api/schedule_jobs', [
            'as_of' => '2026-01-01',
        ]);

        // Assert: Successful response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);

        // Verify log was created
        $this->assertDatabaseHas('holidays_sync_logs', [
            'scheduled_job_id' => $job->id,
            'exchange' => 'NYSE',
            'records_synced' => 15,
            'source' => 'NYSE.com',
        ]);
    }

    #[Test]
    public function test_schedule_jobs_api_returns_holidays_sync_in_response()
    {
        // Arrange
        $schedule = ScheduleExt::create([
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
        $mockService->method('syncHolidays')->willReturn([
            'records_added' => 10,
            'source' => 'test',
        ]);
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act
        $response = $this->actingAs($this->user)->postJson('/api/schedule_jobs', [
            'as_of' => '2026-02-01',
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
        $this->assertEquals(10, $holidaysSyncLog['records_synced']);
    }

    #[Test]
    public function test_schedule_jobs_api_handles_holidays_sync_failure()
    {
        // Arrange
        $schedule = ScheduleExt::create([
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
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->method('syncHolidays')
            ->willThrowException(new \Exception('API connection failed'));
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act
        $response = $this->actingAs($this->user)->postJson('/api/schedule_jobs', [
            'as_of' => '2026-03-01',
        ]);

        // Assert: Should return error
        $response->assertStatus(400); // or whatever error code your controller returns

        // No log should be created
        $this->assertDatabaseMissing('holidays_sync_logs', [
            'scheduled_job_id' => $job->id,
        ]);
    }

    #[Test]
    public function test_holidays_sync_with_entity_descr_filter()
    {
        // Arrange: Create both fund_report and holidays_sync jobs
        $schedule = ScheduleExt::create([
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

        // Mock only holidays sync
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->expects($this->once())
            ->method('syncHolidays')
            ->willReturn([
                'records_added' => 8,
                'source' => 'test',
            ]);
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act: Call with entity filter
        $response = $this->actingAs($this->user)->postJson('/api/schedule_jobs', [
            'as_of' => '2026-04-01',
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
        ]);

        // Assert
        $response->assertStatus(200);

        // Only holidays_sync should have executed
        $this->assertDatabaseHas('holidays_sync_logs', [
            'scheduled_job_id' => $holidaysJob->id,
        ]);
    }

    #[Test]
    public function test_scheduled_job_crud_supports_holidays_sync_entity()
    {
        // Test creating a scheduled job with holidays_sync entity type
        $schedule = ScheduleExt::factory()->create();

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
        // This tests the RunScheduledJobs artisan command
        $schedule = ScheduleExt::create([
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
    }
}
