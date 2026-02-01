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

        // Mock the HolidaySyncService - trait syncs 4 years (prev, current, next 2)
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->expects($this->exactly(4))
            ->method('syncHolidays')
            ->willReturnCallback(function ($exchange, $year) {
                // Return different records per year to test aggregation
                return [
                    'records_added' => match ($year) {
                        2025 => 3,  // Previous year
                        2026 => 5,  // Current year
                        2027 => 4,  // Next year
                        2028 => 3,  // Next+1 year
                        default => 0,
                    },
                    'source' => 'NYSE.com',
                ];
            });

        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act: Execute the handler
        $log = $this->traitObject->holidaysSyncScheduleDue(
            $asOf,
            $job,
            $asOf,
            false
        );

        // Assert: Verify log was created with total records from all years
        $this->assertInstanceOf(HolidaysSyncLog::class, $log);
        $this->assertEquals($job->id, $log->scheduled_job_id);
        $this->assertEquals('NYSE', $log->exchange);
        $this->assertEquals(15, $log->records_synced); // 3+5+4+3=15
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

        // Mock service - trait syncs 4 years, so 10 records per year = 40 total
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->method('syncHolidays')->willReturn([
            'records_added' => 10,
            'source' => 'test',
        ]);
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act
        $log = $this->traitObject->holidaysSyncScheduleDue($asOf, $job, $asOf);

        // Assert: Check database - 4 years × 10 records = 40 total
        $this->assertDatabaseHas('holidays_sync_logs', [
            'id' => $log->id,
            'scheduled_job_id' => $job->id,
            'exchange' => 'NYSE',
            'records_synced' => 40,
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
        // Note: Trait catches exceptions per year and continues, so all years must fail
        // to trigger outer exception handling
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->method('syncHolidays')
            ->willThrowException(new \Exception('API unavailable'));
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act & Assert: Even though individual years fail, the trait catches them
        // and continues. The log should still be created with 0 records synced.
        $log = $this->traitObject->holidaysSyncScheduleDue($asOf, $job, $asOf);

        $this->assertInstanceOf(HolidaysSyncLog::class, $log);
        $this->assertEquals(0, $log->records_synced);
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

        // Mock service - verify it's called with years 2026-2029 (prev, current, +2)
        $mockService = $this->createMock(HolidaySyncService::class);
        $mockService->expects($this->exactly(4))
            ->method('syncHolidays')
            ->willReturnCallback(function ($exchange, $year) {
                // Verify called with correct year range based on asOf=2027
                $this->assertContains($year, [2026, 2027, 2028, 2029]);
                return ['records_added' => 5, 'source' => 'test'];
            });
        $this->app->instance(HolidaySyncService::class, $mockService);

        // Act
        $log = $this->traitObject->holidaysSyncScheduleDue($asOf, $job, $asOf);

        // Assert - synced_at should match the asOf date
        $this->assertEquals(2027, $log->synced_at->year);
        $this->assertEquals(20, $log->records_synced); // 4 years × 5 records
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
