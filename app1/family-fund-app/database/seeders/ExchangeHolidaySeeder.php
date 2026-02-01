<?php

namespace Database\Seeders;

use App\Models\ExchangeHoliday;
use Illuminate\Database\Seeder;

/**
 * Seeder for NYSE and NASDAQ holidays for 2025-2026.
 * These are the official market closure dates.
 *
 * Run with: php artisan db:seed --class=ExchangeHolidaySeeder
 */
class ExchangeHolidaySeeder extends Seeder
{
    /**
     * NYSE holidays for 2025 and 2026
     * Source: https://www.nyse.com/markets/hours-calendars
     */
    private array $nyseHolidays = [
        // 2025
        ['date' => '2025-01-01', 'name' => "New Year's Day"],
        ['date' => '2025-01-20', 'name' => 'Martin Luther King Jr. Day'],
        ['date' => '2025-02-17', 'name' => "Presidents' Day"],
        ['date' => '2025-04-18', 'name' => 'Good Friday'],
        ['date' => '2025-05-26', 'name' => 'Memorial Day'],
        ['date' => '2025-06-19', 'name' => 'Juneteenth National Independence Day'],
        ['date' => '2025-07-04', 'name' => 'Independence Day'],
        ['date' => '2025-09-01', 'name' => 'Labor Day'],
        ['date' => '2025-11-27', 'name' => 'Thanksgiving Day'],
        ['date' => '2025-12-25', 'name' => 'Christmas Day'],

        // 2026
        ['date' => '2026-01-01', 'name' => "New Year's Day"],
        ['date' => '2026-01-19', 'name' => 'Martin Luther King Jr. Day'],
        ['date' => '2026-02-16', 'name' => "Presidents' Day"],
        ['date' => '2026-04-03', 'name' => 'Good Friday'],
        ['date' => '2026-05-25', 'name' => 'Memorial Day'],
        ['date' => '2026-06-19', 'name' => 'Juneteenth National Independence Day'],
        ['date' => '2026-07-03', 'name' => 'Independence Day (observed)'], // July 4 is Saturday
        ['date' => '2026-09-07', 'name' => 'Labor Day'],
        ['date' => '2026-11-26', 'name' => 'Thanksgiving Day'],
        ['date' => '2026-12-25', 'name' => 'Christmas Day'],
    ];

    public function run(): void
    {
        $exchanges = ['NYSE', 'NASDAQ']; // Same holidays for both

        foreach ($exchanges as $exchange) {
            foreach ($this->nyseHolidays as $holiday) {
                ExchangeHoliday::updateOrCreate(
                    [
                        'exchange_code' => $exchange,
                        'holiday_date' => $holiday['date'],
                    ],
                    [
                        'holiday_name' => $holiday['name'],
                        'source' => 'manual-seeder',
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('Seeded ' . count($this->nyseHolidays) . ' holidays for NYSE and NASDAQ');
    }
}
