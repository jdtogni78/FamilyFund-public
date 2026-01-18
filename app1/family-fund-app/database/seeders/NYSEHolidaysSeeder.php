<?php

namespace Database\Seeders;

use App\Models\ExchangeHoliday;
use Illuminate\Database\Seeder;

class NYSEHolidaysSeeder extends Seeder
{
    /**
     * Seed NYSE holidays for 2025-2026
     */
    public function run()
    {
        $holidays = [
            // 2025 NYSE Holidays
            ['exchange_code' => 'NYSE', 'holiday_date' => '2025-01-01', 'holiday_name' => "New Year's Day", 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2025-01-20', 'holiday_name' => 'Martin Luther King Jr. Day', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2025-02-17', 'holiday_name' => "Presidents' Day", 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2025-04-18', 'holiday_name' => 'Good Friday', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2025-05-26', 'holiday_name' => 'Memorial Day', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2025-06-19', 'holiday_name' => 'Juneteenth', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2025-07-04', 'holiday_name' => 'Independence Day', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2025-09-01', 'holiday_name' => 'Labor Day', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2025-11-27', 'holiday_name' => 'Thanksgiving', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2025-12-25', 'holiday_name' => 'Christmas', 'source' => 'manual'],

            // 2026 NYSE Holidays
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-01-01', 'holiday_name' => "New Year's Day", 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-01-19', 'holiday_name' => 'Martin Luther King Jr. Day', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-02-16', 'holiday_name' => "Presidents' Day", 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-04-03', 'holiday_name' => 'Good Friday', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-05-25', 'holiday_name' => 'Memorial Day', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-06-19', 'holiday_name' => 'Juneteenth', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-07-03', 'holiday_name' => 'Independence Day (observed)', 'source' => 'manual'], // July 4 is Saturday
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-09-07', 'holiday_name' => 'Labor Day', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-11-26', 'holiday_name' => 'Thanksgiving', 'source' => 'manual'],
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-12-25', 'holiday_name' => 'Christmas', 'source' => 'manual'],
        ];

        foreach ($holidays as $holiday) {
            ExchangeHoliday::updateOrCreate(
                [
                    'exchange_code' => $holiday['exchange_code'],
                    'holiday_date' => $holiday['holiday_date'],
                ],
                [
                    'holiday_name' => $holiday['holiday_name'],
                    'source' => $holiday['source'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Seeded ' . count($holidays) . ' NYSE holidays for 2025-2026');
    }
}
