<?php

namespace App\Services;

use App\Repositories\ExchangeHolidayRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HolidaySyncService
{
    public function __construct(
        private ExchangeHolidayRepository $repository
    ) {}

    /**
     * Sync holidays from external source
     */
    public function syncHolidays(string $exchange, int $year): array
    {
        try {
            // Fetch from self-hosted Trading Calendar API
            $holidays = $this->fetchFromTradingCalendarAPI($exchange, $year);
        } catch (\Exception $e) {
            Log::error("Trading Calendar API failed: {$e->getMessage()}");
            throw $e;
        }

        if (empty($holidays)) {
            Log::warning("No holidays returned from Trading Calendar API for {$exchange} {$year}");
            return [
                'records_added' => 0,
                'source' => 'trading-calendar-api',
            ];
        }

        $count = $this->repository->bulkUpsert($holidays);

        return [
            'records_added' => $count,
            'source' => 'trading-calendar-api',
        ];
    }

    /**
     * Fetch holidays from self-hosted Trading Calendar API
     *
     * @param string $exchange Exchange code (NYSE, NASDAQ, etc.)
     * @param int $year Year to fetch holidays for
     * @return array Array of holiday records
     * @throws \Exception If API call fails
     */
    private function fetchFromTradingCalendarAPI(string $exchange, int $year): array
    {
        // Map exchange codes to MIC codes
        $micCodes = [
            'NYSE' => 'XNYS',
            'NASDAQ' => 'XNAS',
            'AMEX' => 'XASE',
        ];

        $mic = $micCodes[$exchange] ?? $exchange;

        // Trading Calendar API runs on port 80 in Docker (mapped to 8000 on host)
        $baseUrl = config('services.trading_calendar.url', 'http://trading-calendar:80');

        // Use the holidays endpoint with date range for the year
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";
        $url = "{$baseUrl}/api/v1/markets/holidays?mic={$mic}&start={$startDate}&end={$endDate}";

        Log::info("Fetching holidays from Trading Calendar API: {$url}");

        try {
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                throw new \Exception("Trading Calendar API returned status {$response->status()}");
            }

            $data = $response->json();

            if (!is_array($data)) {
                Log::warning("Trading Calendar API response is not an array", ['response' => $data]);
                return [];
            }

            // Format holidays for database
            // Response is array of: {date, holiday_name, is_business_day, is_early_close, ...}
            $holidays = [];
            foreach ($data as $holiday) {
                $date = $holiday['date'] ?? null;
                $name = $holiday['holiday_name'] ?? 'Unknown Holiday';
                $isBusinessDay = $holiday['is_business_day'] ?? false;

                if (!$date) {
                    continue;
                }

                // Only include non-business days (actual holidays)
                // is_business_day=true means it's an early close day, not a full holiday
                if ($isBusinessDay) {
                    continue;
                }

                $holidays[] = [
                    'exchange_code' => $exchange,
                    'holiday_date' => $date,
                    'holiday_name' => $name,
                    'source' => 'trading-calendar-api',
                ];
            }

            Log::info("Fetched {count} holidays for {$exchange} {$year}", [
                'count' => count($holidays),
                'exchange' => $exchange,
                'year' => $year,
            ]);

            return $holidays;

        } catch (\Exception $e) {
            Log::error("Failed to fetch from Trading Calendar API", [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
