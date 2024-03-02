<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class ScheduleExt extends Schedule
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
        $today = $today->copy()->startOfDay();
        $lastRun = $lastRun?->copy()->startOfDay();
        $prevRunToday = $this->prevRunDate($today);
        $nextRunToday = $this->nextRunDate($today->copy()->addDay());

        // log function call
        Log::info('RS shouldRunBy', [
            'today'        => $today->toDateTimeString(),
            'lastRun'      => $lastRun?->toDateTimeString(),
            'prevRunToday' => $prevRunToday->toDateTimeString(),
            'nextRunToday' => $nextRunToday->toDateTimeString(),
        ]);

        // if last run is null, return prev run date based on today
        if (!$lastRun) {
            Log::info('RS shouldRunBy: lastRun is null: ' . $prevRunToday->toDateString());
            return $prevRunToday;
        }

        // if last run is older than todays prev run, return prev run date based on today
        if ($lastRun->lt($prevRunToday)) {
            Log::info('RS shouldRunBy: lastRun is older than prevRunToday: ' . $prevRunToday->toDateString());
            return $prevRunToday;
        }

        // then, last run is newer than todays prev run, return next run date based on today
        Log::info('RS shouldRunBy: lastRun is newer than prevRunToday: ' . $nextRunToday->toDateString());
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
                $first = $date->copy()->firstOfQuarter();
                $diff = $first->diffInDays($date) + 1;
//                Log::info($date->toDateString() . ' -- '. $first->toDateString() . ' -- ' . $diff);
                return $diff == $value;
        }
    }
}
