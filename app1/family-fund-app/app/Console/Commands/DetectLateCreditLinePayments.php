<?php

namespace App\Console\Commands;

use App\Services\CreditLine\Support\LateDetector;
use Illuminate\Console\Command;

class DetectLateCreditLinePayments extends Command
{
    protected $signature = 'credit-lines:detect-late';
    protected $description = 'Flag overdue scheduled loan-share payment rows as late';

    public function handle(LateDetector $detector): int
    {
        $count = $detector->detectAll();

        $this->info("✓ Flagged {$count} overdue payment row(s) as late.");

        return self::SUCCESS;
    }
}
