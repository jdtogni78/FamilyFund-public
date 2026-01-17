<?php

namespace App\Services;

use App\Models\AssetPrice;
use App\Models\ExchangeHoliday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class AssetPriceGapService
{
    /**
     * Find missing asset price data for trading days
     *
     * @param int $lookbackDays Number of days to check (default 30)
     * @param string $exchange Exchange code (default 'NYSE')
     * @return array Missing dates as array of date strings
     */
    public function findGaps(int $lookbackDays = 30, string $exchange = 'NYSE'): array
    {
        // 1. Get dates with data from database
        $datesWithData = $this->getDatesWithData($lookbackDays);

        // 2. Get expected trading days (exclude weekends/holidays)
        $expectedDates = $this->getExpectedTradingDays($lookbackDays, $exchange);

        // 3. Find missing = expected - actual
        $missingDates = array_diff($expectedDates, $datesWithData);

        // 4. Sort and return as array values (re-index)
        sort($missingDates);

        return array_values($missingDates);
    }

    /**
     * Query database for dates that have asset price records
     *
     * @param int $lookbackDays
     * @return array Array of date strings (Y-m-d format)
     */
    private function getDatesWithData(int $lookbackDays): array
    {
        $cutoffDate = Carbon::now()->subDays($lookbackDays)->startOfDay();

        $dates = DB::table('asset_prices')
            ->select(DB::raw('DISTINCT DATE(start_dt) as price_date'))
            ->where('start_dt', '>=', $cutoffDate)
            ->pluck('price_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        return $dates;
    }

    /**
     * Generate expected trading days (exclude weekends and holidays)
     *
     * @param int $lookbackDays
     * @param string $exchange
     * @return array Array of date strings (Y-m-d format)
     */
    private function getExpectedTradingDays(int $lookbackDays, string $exchange): array
    {
        $startDate = Carbon::now()->subDays($lookbackDays - 1)->startOfDay();
        $endDate = Carbon::now()->startOfDay();

        // Get holidays for the date range
        $holidays = $this->getHolidays($exchange, $startDate, $endDate);

        // Generate all dates in range
        $period = CarbonPeriod::create($startDate, $endDate);
        $tradingDays = [];

        foreach ($period as $date) {
            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            // Skip holidays
            $dateStr = $date->format('Y-m-d');
            if (in_array($dateStr, $holidays)) {
                continue;
            }

            $tradingDays[] = $dateStr;
        }

        return $tradingDays;
    }

    /**
     * Get holidays for exchange in date range
     *
     * @param string $exchange
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array Array of holiday date strings (Y-m-d format)
     */
    private function getHolidays(string $exchange, Carbon $startDate, Carbon $endDate): array
    {
        $holidays = ExchangeHoliday::active()
            ->forExchange($exchange)
            ->whereBetween('holiday_date', [$startDate, $endDate])
            ->pluck('holiday_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        return $holidays;
    }
}
