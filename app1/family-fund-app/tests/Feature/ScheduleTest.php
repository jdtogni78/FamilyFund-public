<?php
namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\ScheduleExt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
// class Eloquent not found
use Illuminate\Database\Eloquent\Model;

class ScheduleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    // create a parametrized test
    public static function scheduleProvider()
    {
        return [
            ['2024-02-04',         null, '2024-01-05'],
            ['2024-02-05',         null, '2024-02-05'],
            ['2024-02-05', '2024-02-04', '2024-02-05'],
            ['2024-02-05', '2024-02-05', '2024-03-05'],
            ['2024-02-05 12:00:00', '2024-02-05', '2024-03-05'], // ignore time
            ['2024-02-05', '2024-02-05 12:00:00', '2024-03-05'], // ignore time
            ['2024-02-05', '2024-02-06', '2024-03-05'], // maybe should error out as lan run is newer than today

            ['2024-02-15',         null, '2024-02-05'],
            ['2024-02-15', '2024-02-04', '2024-02-05'],
            ['2024-02-15', '2024-02-05', '2024-03-05'],

            ['2024-03-04', '2024-02-04', '2024-02-05'],
            ['2024-03-05', '2024-02-04', '2024-03-05'], // today we will just skip, even when was not run prev period
        ];
    }

    #[DataProvider('scheduleProvider')]
    public function testSchedules($today, $lastRun, $expected)
    {
        $rs = Schedule::factory()->make(['type' => ScheduleExt::TYPE_DAY_OF_MONTH, 'value' => '5']);
        $runBy = $rs->shouldRunBy(new Carbon($today), $lastRun == null? null : new Carbon($lastRun));
        $this->assertEquals($expected, $runBy->toDateString());
    }


    // create a parametrized test for different types and values
    public static function typesAndValuesProvider()
    {
        return [
            ['2023-12-27', 370, ScheduleExt::TYPE_DAY_OF_MONTH, '5', 12],
            ['2023-12-27', 370, ScheduleExt::TYPE_DAY_OF_WEEK, '5', 53],

            ['2023-12-27', 370, ScheduleExt::TYPE_DAY_OF_YEAR, '5', 1],
            ['2023-12-27',   8, ScheduleExt::TYPE_DAY_OF_YEAR, '5', 0],
            ['2023-12-27',   9, ScheduleExt::TYPE_DAY_OF_YEAR, '5', 1],
            ['2023-12-27',   5, ScheduleExt::TYPE_DAY_OF_YEAR, '1', 1],
            ['2023-12-31', 367, ScheduleExt::TYPE_DAY_OF_YEAR, '1', 2],

            ['2023-12-27', 370, ScheduleExt::TYPE_DAY_OF_QUARTER, '5', 4],
            ['2023-01-01',   3, ScheduleExt::TYPE_DAY_OF_QUARTER, '5', 0],
            ['2023-01-01',   4, ScheduleExt::TYPE_DAY_OF_QUARTER, '5', 1],
        ];
    }

    #[DataProvider('typesAndValuesProvider')]
    public function testTypesAndValues($startDate, $days, $type, $value, $expected) {
        Log::info("startDate: $startDate, days: $days, type: $type, value: $value, expected: $expected");
        $rs = Schedule::factory()->make(['type' => $type, 'value' => $value]);
        $count = 0;
        $today = new Carbon($startDate);
        for ($i = 0; $i <= $days; $i++) {
            if ($rs->matchesSchedule($today)) {
                Log::info("matches: " . $today->toDateString());
                $count++;
            }
            $today->addDay();
        }
        $today->subDay();
        Log::info("count: $count by " . $today->toDateString());

        $this->assertEquals($expected, $count);
    }
}
