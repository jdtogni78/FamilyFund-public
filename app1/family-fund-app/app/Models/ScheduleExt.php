<?php

namespace App\Models;

use App\Http\Controllers\Traits\VerboseTrait;
use Carbon\Carbon;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class ScheduleExt extends Schedule
{
    use VerboseTrait;

    const TYPE_DAY_OF_MONTH = 'DOM';
    const TYPE_DAY_OF_WEEK = 'DOW';
    const TYPE_DAY_OF_YEAR = 'DOY';
    const TYPE_DAY_OF_QUARTER = 'DOQ';

    /**
     * @var string[]
     */
    public static array $typeMap = [
        self::TYPE_DAY_OF_MONTH => 'Day of Month',
        self::TYPE_DAY_OF_WEEK => 'Day of Week',
        self::TYPE_DAY_OF_YEAR => 'Day of Year',
        self::TYPE_DAY_OF_QUARTER => 'Day of Quarter',
    ];

    public function shouldRunBy(Carbon $today, null|Carbon $lastRun) {
        $today = $today->copy()->startOfDay();
        $lastRun = $lastRun?->copy()->startOfDay();
        $prevRunToday = $this->prevRunDate($today);
        $nextRunToday = $this->nextRunDate($today->copy()->addDay());

        // log function call
        $this->debug('RS shouldRunBy', [
            'today'        => $today->toDateTimeString(),
            'lastRun'      => $lastRun?->toDateTimeString(),
            'prevRunToday' => $prevRunToday->toDateTimeString(),
            'nextRunToday' => $nextRunToday->toDateTimeString(),
        ]);

        // if last run is null, return prev run date based on today
        if (!$lastRun) {
            $this->info('RS shouldRunBy: lastRun is null: ' . $prevRunToday->toDateString());
            return $prevRunToday;
        }

        // if last run is older than todays prev run, return prev run date based on today
        if ($lastRun->lt($prevRunToday)) {
            $this->info('RS shouldRunBy: lastRun is older than prevRunToday: ' . $prevRunToday->toDateString());
            return $prevRunToday;
        }

        // then, last run is newer than todays prev run, return next run date based on today
        $this->info('RS shouldRunBy: lastRun is newer than prevRunToday: ' . $nextRunToday->toDateString());
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
            case self::TYPE_DAY_OF_MONTH:
                return $date->day == $value;
            case self::TYPE_DAY_OF_WEEK:
                return $date->dayOfWeek == $value;
            case self::TYPE_DAY_OF_YEAR:
                return $date->dayOfYear == $value;
            case self::TYPE_DAY_OF_QUARTER:
                $first = $date->copy()->firstOfQuarter();
                $diff = $first->diffInDays($date) + 1;
//                $this->info($date->toDateString() . ' -- '. $first->toDateString() . ' -- ' . $diff);
                return $diff == $value;
        }
    }
}
