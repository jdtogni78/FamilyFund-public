<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;

/**
 * Class Utils
 * @package App\Models
 */
class Utils
{
    public static function currency($value)
    {
        return round($value, 2);
    }

    public static function shares($value)
    {
        return floor($value * 10000)/10000;
    }
    public static function position($value)
    {
        return floor($value * 100000000)/100000000;
    }

    public static function percent($value)
    {
        return round($value * 100,2);
    }

    public static function decreaseYearMonth($yearMonth)
    {
        $month = $yearMonth[1];
        if ($month > 12) { // something wrong
            throw new \Exception("Invalid month in YearMonth ( " . $yearMonth[0].'-'.$yearMonth[1] . ")");
        }
        $year = $yearMonth[0];
        if ($year < 1970 || $year > 2100) {
            throw new \Exception("Invalid year in YearMonth ( " . $yearMonth[0].'-'.$yearMonth[1] . ")");
        }

        if ($month == 1) { // 2001 01 => 2000 12
            $yearMonth[1] += 11; // roll year
            $yearMonth[0] -= 1;
        } else {
            $yearMonth[1]--; // roll month
        }
        return $yearMonth;
    }

    public static function yearMonthInt($yearMonth)
    {
        return $yearMonth[0]*100 + $yearMonth[1];
    }

    public static function asOfAddYear($asOf, int $offset)
    {
        $year = substr($asOf,0,4) + $offset;
        $prevYearAsOf = $year . substr($asOf, 4);
        return $prevYearAsOf;
    }
}
