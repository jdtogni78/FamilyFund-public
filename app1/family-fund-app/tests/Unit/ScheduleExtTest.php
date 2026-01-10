<?php

namespace Tests\Unit;

use App\Models\Schedule;
use App\Models\ScheduleExt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Unit tests for ScheduleExt model
 */
class ScheduleExtTest extends TestCase
{
    use DatabaseTransactions;

    public function test_type_constants_defined()
    {
        $this->assertEquals('DOM', ScheduleExt::TYPE_DAY_OF_MONTH);
        $this->assertEquals('DOW', ScheduleExt::TYPE_DAY_OF_WEEK);
        $this->assertEquals('DOY', ScheduleExt::TYPE_DAY_OF_YEAR);
        $this->assertEquals('DOQ', ScheduleExt::TYPE_DAY_OF_QUARTER);
    }

    public function test_type_map_contains_all_types()
    {
        $this->assertArrayHasKey(ScheduleExt::TYPE_DAY_OF_MONTH, ScheduleExt::$typeMap);
        $this->assertArrayHasKey(ScheduleExt::TYPE_DAY_OF_WEEK, ScheduleExt::$typeMap);
        $this->assertArrayHasKey(ScheduleExt::TYPE_DAY_OF_YEAR, ScheduleExt::$typeMap);
        $this->assertArrayHasKey(ScheduleExt::TYPE_DAY_OF_QUARTER, ScheduleExt::$typeMap);
    }

    public function test_matches_schedule_day_of_month()
    {
        $schedule = Schedule::factory()->create([
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => 15,
        ]);
        $scheduleExt = ScheduleExt::find($schedule->id);

        // Day 15 should match
        $this->assertTrue($scheduleExt->matchesSchedule(Carbon::parse('2022-01-15')));
        $this->assertTrue($scheduleExt->matchesSchedule(Carbon::parse('2022-06-15')));

        // Day 14 should not match
        $this->assertFalse($scheduleExt->matchesSchedule(Carbon::parse('2022-01-14')));
    }

    public function test_matches_schedule_day_of_week()
    {
        $schedule = Schedule::factory()->create([
            'type' => ScheduleExt::TYPE_DAY_OF_WEEK,
            'value' => 1, // Monday
        ]);
        $scheduleExt = ScheduleExt::find($schedule->id);

        // Monday should match
        $this->assertTrue($scheduleExt->matchesSchedule(Carbon::parse('2022-01-03'))); // Monday

        // Tuesday should not match
        $this->assertFalse($scheduleExt->matchesSchedule(Carbon::parse('2022-01-04'))); // Tuesday
    }

    public function test_matches_schedule_day_of_year()
    {
        $schedule = Schedule::factory()->create([
            'type' => ScheduleExt::TYPE_DAY_OF_YEAR,
            'value' => 1, // Jan 1
        ]);
        $scheduleExt = ScheduleExt::find($schedule->id);

        // Jan 1 should match
        $this->assertTrue($scheduleExt->matchesSchedule(Carbon::parse('2022-01-01')));

        // Jan 2 should not match
        $this->assertFalse($scheduleExt->matchesSchedule(Carbon::parse('2022-01-02')));
    }

    public function test_matches_schedule_day_of_quarter()
    {
        $schedule = Schedule::factory()->create([
            'type' => ScheduleExt::TYPE_DAY_OF_QUARTER,
            'value' => 1, // First day of quarter
        ]);
        $scheduleExt = ScheduleExt::find($schedule->id);

        // First day of Q1 (Jan 1)
        $this->assertTrue($scheduleExt->matchesSchedule(Carbon::parse('2022-01-01')));
        // First day of Q2 (Apr 1)
        $this->assertTrue($scheduleExt->matchesSchedule(Carbon::parse('2022-04-01')));

        // Second day of Q1 should not match
        $this->assertFalse($scheduleExt->matchesSchedule(Carbon::parse('2022-01-02')));
    }

    public function test_next_run_date_finds_next_matching_date()
    {
        $schedule = Schedule::factory()->create([
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => 15,
        ]);
        $scheduleExt = ScheduleExt::find($schedule->id);

        // From Jan 10, next run should be Jan 15
        $result = $scheduleExt->nextRunDate(Carbon::parse('2022-01-10'));
        $this->assertEquals('2022-01-15', $result->toDateString());
    }

    public function test_prev_run_date_finds_previous_matching_date()
    {
        $schedule = Schedule::factory()->create([
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => 15,
        ]);
        $scheduleExt = ScheduleExt::find($schedule->id);

        // From Jan 20, prev run should be Jan 15
        $result = $scheduleExt->prevRunDate(Carbon::parse('2022-01-20'));
        $this->assertEquals('2022-01-15', $result->toDateString());
    }

    public function test_should_run_by_returns_prev_run_when_never_run()
    {
        $schedule = Schedule::factory()->create([
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => 15,
        ]);
        $scheduleExt = ScheduleExt::find($schedule->id);

        // Never run before, should return prev run date
        $result = $scheduleExt->shouldRunBy(Carbon::parse('2022-01-20'), null);
        $this->assertEquals('2022-01-15', $result->toDateString());
    }

    public function test_should_run_by_returns_prev_run_when_last_run_older()
    {
        $schedule = Schedule::factory()->create([
            'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
            'value' => 15,
        ]);
        $scheduleExt = ScheduleExt::find($schedule->id);

        // Last run was Dec 15, today is Jan 20 - should return Jan 15
        $result = $scheduleExt->shouldRunBy(
            Carbon::parse('2022-01-20'),
            Carbon::parse('2021-12-15')
        );
        $this->assertEquals('2022-01-15', $result->toDateString());
    }
}
