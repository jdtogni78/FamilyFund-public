<?php

namespace App\Console\Commands;

use App\Services\HolidaySyncService;
use Illuminate\Console\Command;

class SyncExchangeHolidays extends Command
{
    protected $signature = 'holidays:sync {exchange} {year?}';
    protected $description = 'Sync exchange holidays from external sources';

    public function handle(HolidaySyncService $service)
    {
        $exchange = $this->argument('exchange');
        $year = $this->argument('year') ?? now()->year;

        $this->info("Syncing holidays for {$exchange} {$year}...");

        $result = $service->syncHolidays($exchange, $year);

        $this->info("✓ Synced {$result['records_added']} holidays from {$result['source']}");
    }
}
