<?php

namespace Tests\Feature;

use App\Models\HolidaysSyncLog;
use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use App\Models\ScheduleExt;
use App\Models\User;
use App\Services\HolidaySyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Feature tests for Scheduled Holiday Sync functionality
 * Tests end-to-end scheduled job execution for holiday synchronization
 */
class ScheduledHolidaySyncTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_scheduled_holiday_sync_executes_on_due_date()
    {
        // Arrange: Create monthly schedule (1st of month)
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
            ->willReturn([
                'records_added' => 12,
                'source' => 'NYSE.com',
            ]);
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act: Run scheduled jobs on 1st of month
        $asOf = Carbon::parse('2026-02-01');
        $response = $this->actingAs($this->user)
            ->postJson('/api/schedule_jobs', [
                'as_of' => $asOf->toDateString(),
            ]);

        // Assert
        $response->assertStatus(200);

        // Verify log was created
        $this->assertDatabaseHas('holidays_sync_logs', [
            'scheduled_job_id' => $job->id,
            'exchange' => 'NYSE',
            'records_synced' => 12,
        ]);

        $log = HolidaysSyncLog::where('scheduled_job_id', $job->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('NYSE.com', $log->source);
    }

    public function test_scheduled_holiday_sync_skips_when_not_due()
    {
        // Arrange: Create monthly schedule (1st of month)
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

        // Mock service - should NOT be called
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->expects($this->never())
            ->method('syncHolidays');
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act: Run on 15th of month (not due)
        $asOf = Carbon::parse('2026-02-15');
        $response = $this->actingAs($this->user)
            ->postJson('/api/schedule_jobs', [
                'as_of' => $asOf->toDateString(),
            ]);

        // Assert: No sync log created
        $this->assertDatabaseMissing('holidays_sync_logs', [
            'scheduled_job_id' => $job->id,
        ]);
    }

    public function test_multiple_scheduled_holiday_syncs_execute_independently()
    {
        // Arrange: Create two different schedules
        $monthlySchedule = ScheduleExt::create([
            'descr' => 'Monthly - 1st',
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => '1',
        ]);

        $quarterlySchedule = ScheduleExt::create([
            'descr' => 'Quarterly - 1st',
            'type' => ScheduleExt::TYPE_DAY_OF_QUARTER,
            'value' => '1',
        ]);

        $monthlyJob = ScheduledJob::create([
            'schedule_id' => $monthlySchedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
            'start_dt' => Carbon::now()->subMonth(),
            'end_dt' => Carbon::now()->addYear(),
        ]);

        $quarterlyJob = ScheduledJob::create([
            'schedule_id' => $quarterlySchedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 2,
            'start_dt' => Carbon::now()->subMonth(),
            'end_dt' => Carbon::now()->addYear(),
        ]);

        // Mock service
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->expects($this->exactly(2))
            ->method('syncHolidays')
            ->willReturn([
                'records_added' => 10,
                'source' => 'test',
            ]);
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act: Run on Jan 1 (both monthly and quarterly due)
        $asOf = Carbon::parse('2026-01-01');
        $response = $this->actingAs($this->user)
            ->postJson('/api/schedule_jobs', [
                'as_of' => $asOf->toDateString(),
            ]);

        // Assert: Both jobs executed
        $response->assertStatus(200);

        $this->assertDatabaseHas('holidays_sync_logs', [
            'scheduled_job_id' => $monthlyJob->id,
        ]);

        $this->assertDatabaseHas('holidays_sync_logs', [
            'scheduled_job_id' => $quarterlyJob->id,
        ]);
    }

    public function test_scheduled_holiday_sync_tracks_execution_time()
    {
        // Arrange
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

        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->method('syncHolidays')->willReturn([
            'records_added' => 5,
            'source' => 'test',
        ]);
        $this->app->instance(HolidaySyncService::class, $mockService);

        $expectedDate = Carbon::parse('2026-03-01 10:00:00');

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/schedule_jobs', [
                'as_of' => $expectedDate->toDateString(),
            ]);

        // Assert
        $response->assertStatus(200);

        $log = HolidaysSyncLog::where('scheduled_job_id', $job->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals($expectedDate->toDateString(), $log->synced_at->toDateString());
        $this->assertNotNull($log->created_at);
        $this->assertNotNull($log->updated_at);
    }

    public function test_holidays_sync_seeder_creates_default_schedule()
    {
        // Act: Run the seeder
        $this->artisan('db:seed', ['--class' => 'HolidaySyncScheduleSeeder']);

        // Assert: Check schedule exists
        $schedule = ScheduleExt::where('type', ScheduleExt::TYPE_DAY_OF_MONTH)
            ->where('value', '1')
            ->first();

        $this->assertNotNull($schedule);

        // Check scheduled job exists
        $job = ScheduledJob::where('entity_descr', ScheduledJobExt::ENTITY_HOLIDAYS_SYNC)
            ->where('schedule_id', $schedule->id)
            ->first();

        $this->assertNotNull($job);
        $this->assertEquals(1, $job->entity_id);
    }

    public function test_scheduled_holiday_sync_logs_are_queryable()
    {
        // Arrange: Create multiple logs
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

        HolidaysSyncLog::create([
            'scheduled_job_id' => $job->id,
            'exchange' => 'NYSE',
            'synced_at' => Carbon::parse('2026-01-01'),
            'records_synced' => 10,
            'source' => 'NYSE.com',
        ]);

        HolidaysSyncLog::create([
            'scheduled_job_id' => $job->id,
            'exchange' => 'NYSE',
            'synced_at' => Carbon::parse('2026-02-01'),
            'records_synced' => 12,
            'source' => 'NYSE.com',
        ]);

        // Act: Query logs
        $logs = HolidaysSyncLog::where('exchange', 'NYSE')
            ->where('scheduled_job_id', $job->id)
            ->orderBy('synced_at', 'desc')
            ->get();

        // Assert
        $this->assertCount(2, $logs);
        $this->assertEquals(12, $logs->first()->records_synced); // Most recent
        $this->assertEquals(10, $logs->last()->records_synced); // Oldest
    }
}
