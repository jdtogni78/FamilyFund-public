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
            throw new \InvalidArgumentException("Invalid month in YearMonth ( " . $yearMonth[0].'-'.$yearMonth[1] . ")");
        }
        $year = $yearMonth[0];
        if ($year < 1970 || $year > 2100) {
            throw new \InvalidArgumentException("Invalid year in YearMonth ( " . $yearMonth[0].'-'.$yearMonth[1] . ")");
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
        // The string + int math below silently fails on non-numeric prefixes
        // ("Unsupported operand types: string + int") so reject early.
        if (! is_string($asOf) || ! preg_match('/^\d{4}-\d{2}-\d{2}/', $asOf)) {
            throw new \InvalidArgumentException("Invalid as_of date '" . (is_string($asOf) ? $asOf : gettype($asOf)) . "', expected YYYY-MM-DD");
        }
        $year = (int) substr($asOf, 0, 4) + $offset;
        $prevYearAsOf = $year . substr($asOf, 4);
        return $prevYearAsOf;
    }

    /**
     * Normalize an as_of string supplied by a request. Returns a YYYY-MM-DD
     * date string. Empty / null yield today. Year is clamped to [1970, 2100]
     * (the range Utils::decreaseYearMonth supports) so far-future sentinels
     * like 9999-12-31 don't crash downstream reports.
     *
     * Throws InvalidArgumentException on malformed input — callers should
     * either let the framework convert that to 404 / 400 or catch and flash.
     */
    public static function sanitizeAsOf(?string $asOf): string
    {
        if ($asOf === null || $asOf === '') {
            return date('Y-m-d');
        }

        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $asOf, $m)) {
            throw new \InvalidArgumentException("Invalid as_of date '{$asOf}', expected YYYY-MM-DD");
        }

        $year = (int) $m[1];
        $month = (int) $m[2];
        $day = (int) $m[3];
        if (! checkdate($month, $day, max(1, $year))) {
            throw new \InvalidArgumentException("Invalid as_of date '{$asOf}'");
        }

        if ($year < 1970) {
            return sprintf('%04d-%02d-%02d', 1970, $month, $day);
        }
        if ($year > 2100) {
            return sprintf('%04d-%02d-%02d', 2100, $month, $day);
        }
        return $asOf;
    }
}
