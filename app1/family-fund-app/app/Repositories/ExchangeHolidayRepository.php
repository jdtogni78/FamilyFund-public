<?php

namespace App\Repositories;

use App\Models\ExchangeHoliday;
use Illuminate\Support\Collection;

class ExchangeHolidayRepository
{
    public function getHolidays(string $exchange, int $year): Collection
    {
        return ExchangeHoliday::active()
            ->forExchange($exchange)
            ->forYear($year)
            ->orderBy('holiday_date')
            ->get();
    }

    public function upsertHoliday(array $data): ExchangeHoliday
    {
        return ExchangeHoliday::updateOrCreate(
            [
                'exchange_code' => $data['exchange_code'],
                'holiday_date' => $data['holiday_date'],
            ],
            $data
        );
    }

    public function bulkUpsert(array $holidays): int
    {
        $count = 0;
        foreach ($holidays as $holiday) {
            $this->upsertHoliday($holiday);
            $count++;
        }
        return $count;
    }
}
