<?php

namespace App\Console\Commands;

use App\Http\Controllers\Traits\DetectsDataIssuesTrait;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Independent safety-net alert for the daily asset-price feed (#39, salvage of
 * the #31 work). The daily price post can stop for many reasons (FamilyFund
 * down, dstrader not running, POST rejected). This command runs daily on its
 * own schedule and emails ADMIN_EMAILS / exits non-zero when the newest price
 * for any tracked asset has fallen more than --threshold trading days behind
 * the report date — regardless of root cause.
 *
 * Cash (CSH) assets are excluded; their price never advances and so is
 * trivially "stale" every day.
 */
class CheckPriceStaleness extends Command
{
    use DetectsDataIssuesTrait;

    protected $signature = 'prices:check-staleness
        {--threshold=5 : Max trading days the newest price may lag behind today before alerting}
        {--exchange=NYSE : Exchange holiday calendar to subtract from the trading-day count}
        {--as-of= : Report date (Y-m-d). Defaults to today. Useful for testing.}';

    protected $description = 'Alert when the newest asset price falls more than N trading days behind today (independent of the daily ingestion pipeline).';

    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');
        $exchange = (string) $this->option('exchange');
        $asOf = $this->option('as-of') ? Carbon::parse($this->option('as-of')) : Carbon::now();

        $latestPrices = AssetPrice::with('asset')
            ->whereYear('end_dt', 9999)
            ->get();

        $latestPrices = $latestPrices->filter(function (AssetPrice $p) {
            $asset = $p->asset;
            if (! $asset) {
                return false;
            }
            return ! ($asset instanceof AssetExt ? $asset->isCash() : ($asset->name === 'CASH' || $asset->type === 'CSH'));
        })->values();

        if ($latestPrices->isEmpty()) {
            $this->info('No tracked asset prices to check.');
            return self::SUCCESS;
        }

        $holidays = $this->loadExchangeHolidays($exchange, $latestPrices->min('start_dt'), $asOf);

        $stale = [];
        foreach ($latestPrices as $price) {
            $start = Carbon::parse($price->start_dt);
            if ($start >= $asOf) {
                continue;
            }
            $tradingDays = $this->calculateTradingDays($start, $asOf, $holidays);
            if ($tradingDays > $threshold) {
                $stale[] = [
                    'name' => $price->asset->name ?? "asset #{$price->asset_id}",
                    'asset_id' => $price->asset_id,
                    'from' => $start->format('Y-m-d'),
                    'to' => $asOf->format('Y-m-d'),
                    'days' => $tradingDays,
                ];
            }
        }

        if (empty($stale)) {
            $this->info("OK: no asset prices lag more than {$threshold} trading days behind {$asOf->format('Y-m-d')}.");
            return self::SUCCESS;
        }

        usort($stale, fn($a, $b) => $b['days'] <=> $a['days']);

        $summary = sprintf(
            'Price staleness: %d asset(s) have prices more than %d trading days behind %s. Worst: %s @ %d trading days.',
            count($stale),
            $threshold,
            $asOf->format('Y-m-d'),
            $stale[0]['name'],
            $stale[0]['days']
        );

        Log::error('[prices:check-staleness] ' . $summary, ['stale' => $stale]);
        $this->error($summary);
        foreach ($stale as $row) {
            $this->line(sprintf('  - %s: last price %s (%d trading days)', $row['name'], $row['from'], $row['days']));
        }

        try {
            $recipients = (array) config('familyfund.admin_emails', []);
            $recipients = array_values(array_filter($recipients));
            if (! empty($recipients)) {
                Mail::send('emails.price_staleness_alert', [
                    'stale' => $stale,
                    'threshold' => $threshold,
                    'asOf' => $asOf,
                ], function ($m) use ($recipients) {
                    $m->to($recipients)
                      ->subject('[FamilyFund] Asset price feed appears stalled');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('[prices:check-staleness] alert email failed: ' . $e->getMessage());
        }

        return self::FAILURE;
    }
}
