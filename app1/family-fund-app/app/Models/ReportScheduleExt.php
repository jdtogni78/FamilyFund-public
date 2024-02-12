<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

/**
 * Class ReportSchedule
 * @package App\Models
 * @version February 11, 2024, 7:23 pm UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $fundReportSchedules
 * @property string $descr
 * @property string $type
 * @property string $value
 */
class ReportScheduleExt extends ReportSchedule
{
    /**
     * @var string[]
     */
    public static array $typeMap = [
        'DOM' => 'Day of Month',
        'DOW' => 'Day of Week',
        'DOY' => 'Day of Year',
        'DOQ' => 'Day of Quarter',
    ];

    public function shouldRunBy(Carbon $today, null|Carbon $lastRun) {
        $prevRunToday = $this->prevRunDate($today);
        $nextRunToday = $this->nextRunDate($today);

        // log function call
        Log::info('shouldRunBy', [
            'today' => $today->toDateString(),
            'lastRun' => $lastRun?->toDateString(),
            'prevRunToday' => $prevRunToday->toDateString(),
            'nextRunToday' => $nextRunToday->toDateString(),
        ]);

        // if last run is null, return prev run date based on today
        if (!$lastRun) {
            return $prevRunToday;
        }

        // if last run is older than todays prev run, return prev run date based on today
        if ($lastRun->lt($prevRunToday)) {
            return $prevRunToday;
        }

        // then, last run is newer than todays prev run, return next run date based on today
        return $nextRunToday;
    }

    public function nextRunDate(Carbon $asOf) {
        return $this->nextDate($asOf, 1);
    }

    public function prevRunDate(Carbon $asOf) {
        return $this->nextDate($asOf, -1);
    }

    public function nextDate(Carbon $asOf, int $count = 1) {
        $nextRun = $asOf->copy();
        while (!$this->matchesSchedule($nextRun)) {
            $nextRun = $nextRun->addDays($count);
        }
        return $nextRun;
    }

    public function matchesSchedule(Carbon $date) {
        $type = $this->type;
        $value = $this->value;
        switch ($type) {
            case 'DOM':
                return $date->day == $value;
            case 'DOW':
                return $date->dayOfWeek == $value;
            case 'DOY':
                return $date->dayOfYear == $value;
            case 'DOQ':
                return $date->firstOfQuarter()->diffInDays($date) == $value;
        }
    }
}
