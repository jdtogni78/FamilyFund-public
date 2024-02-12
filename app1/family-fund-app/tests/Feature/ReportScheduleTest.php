<?php
namespace Tests\Feature;

use App\Models\ReportSchedule;
use Carbon\Carbon;
use Tests\TestCase;

class ReportScheduleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    // create a parametrized test
    public function scheduleProvider()
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

    /**
     * @dataProvider scheduleProvider
     */
    public function testSchedules($today, $lastRun, $expected)
    {
        $rs = ReportSchedule::factory()->make(['type' => 'DOM', 'value' => '5']);
        $runBy = $rs->shouldRunBy(new Carbon($today), $lastRun == null? null : new Carbon($lastRun));
        $this->assertEquals($expected, $runBy->toDateString());
    }
}
