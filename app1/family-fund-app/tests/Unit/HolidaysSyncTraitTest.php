<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\HolidaysSyncTrait;
use App\Models\HolidaysSyncLog;
use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use App\Models\Schedule;
use App\Services\HolidaySyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Unit tests for HolidaysSyncTrait
 * Tests the handler logic for scheduled holiday sync jobs
 */
class HolidaysSyncTraitTest extends TestCase
{
    use DatabaseTransactions;

    private $traitObject;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous class that uses the trait and exposes the method as public
        $this->traitObject = new class {
            use HolidaysSyncTrait {
                holidaysSyncScheduleDue as public;
            }
        };
    }

    public function test_holidays_sync_schedule_due_creates_log()
    {
        // Arrange: Create a scheduled job for holidays_sync
        $schedule = Schedule::factory()->create([
            'type' => 'DOM', // Day of month
            'value' => '1',
        ]);

        $job = ScheduledJob::factory()->create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
        ]);

        $asOf = Carbon::parse('2026-01-15');

        // Mock the HolidaySyncService
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->expects($this->once())
            ->method('syncHolidays')
            ->with('NYSE', 2026)
            ->willReturn([
                'records_added' => 15,
                'records_updated' => 0,
                'source' => 'NYSE.com',
                'total_holidays' => 15,
            ]);

        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act: Execute the handler
        $log = $this->traitObject->holidaysSyncScheduleDue(
            $asOf,
            $job,
            $asOf,
            false
        );

        // Assert: Verify log was created
        $this->assertInstanceOf(HolidaysSyncLog::class, $log);
        $this->assertEquals($job->id, $log->scheduled_job_id);
        $this->assertEquals('NYSE', $log->exchange);
        $this->assertEquals(15, $log->records_synced);
        $this->assertEquals('NYSE.com', $log->source);
        $this->assertTrue($log->synced_at->equalTo($asOf));
    }

    public function test_holidays_sync_schedule_due_persists_to_database()
    {
        // Arrange
        $schedule = Schedule::factory()->create();
        $job = ScheduledJob::factory()->create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
        ]);

        $asOf = Carbon::parse('2026-02-01');

        // Mock service
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->method('syncHolidays')->willReturn([
            'records_added' => 10,
            'source' => 'test',
        ]);
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act
        $log = $this->traitObject->holidaysSyncScheduleDue($asOf, $job, $asOf);

        // Assert: Check database
        $this->assertDatabaseHas('holidays_sync_logs', [
            'id' => $log->id,
            'scheduled_job_id' => $job->id,
            'exchange' => 'NYSE',
            'records_synced' => 10,
        ]);
    }

    public function test_holidays_sync_schedule_due_handles_service_failure()
    {
        // Arrange
        $schedule = Schedule::factory()->create();
        $job = ScheduledJob::factory()->create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
        ]);

        $asOf = Carbon::now();

        // Mock service to throw exception
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->method('syncHolidays')
            ->willThrowException(new \Exception('API unavailable'));
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act & Assert: Should propagate exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API unavailable');

        $this->traitObject->holidaysSyncScheduleDue($asOf, $job, $asOf);
    }

    public function test_holidays_sync_uses_correct_year_from_as_of_date()
    {
        // Arrange
        $schedule = Schedule::factory()->create();
        $job = ScheduledJob::factory()->create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
            'entity_id' => 1,
        ]);

        $asOf = Carbon::parse('2027-06-15'); // Mid-year 2027

        // Mock service - verify it's called with year 2027
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->expects($this->once())
            ->method('syncHolidays')
            ->with('NYSE', 2027) // Should use year from $asOf
            ->willReturn(['records_added' => 5, 'source' => 'test']);
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act
        $log = $this->traitObject->holidaysSyncScheduleDue($asOf, $job, $asOf);

        // Assert
        $this->assertEquals(2027, $log->synced_at->year);
    }

    public function test_holidays_sync_log_belongs_to_scheduled_job()
    {
        // Arrange
        $schedule = Schedule::factory()->create();
        $job = ScheduledJob::factory()->create([
            'schedule_id' => $schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
        ]);

        $log = HolidaysSyncLog::create([
            'scheduled_job_id' => $job->id,
            'exchange' => 'NYSE',
            'synced_at' => Carbon::now(),
            'records_synced' => 10,
            'source' => 'test',
        ]);

        // Act
        $relatedJob = $log->scheduledJob;

        // Assert
        $this->assertNotNull($relatedJob);
        $this->assertEquals($job->id, $relatedJob->id);
        $this->assertEquals(ScheduledJobExt::ENTITY_HOLIDAYS_SYNC, $relatedJob->entity_descr);
    }
}
