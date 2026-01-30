<?php

namespace App\Models;

use App\Http\Controllers\Traits\VerboseTrait;
use App\Repositories\PortfolioAssetRepository;
use App\Repositories\TradePortfolioRepository;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class PortfolioExt
 * @package App\Models
 */
class PortfolioExt extends Portfolio
{
    use VerboseTrait;

    public function assetsAsOf($now, $assetId=null): Collection
    {
        $portfolioAssetsRepo = \App::make(PortfolioAssetRepository::class);
        $query = $portfolioAssetsRepo->makeModel()->newQuery()
            ->where('portfolio_id', $this->id)
            ->whereDate('start_dt', '<=', $now)
            ->whereDate('end_dt', '>', $now);
        if ($assetId) $query = $query->where('asset_id', $assetId);
        $portfolioAssets = $query->get();
        return $portfolioAssets;
    }

    public function tradePortfoliosBetween($start, $end): Collection
    {
        $tradePortfolioAssetsRepo = \App::make(TradePortfolioRepository::class);
        $query = $tradePortfolioAssetsRepo->makeModel()->newQuery()
            ->where('portfolio_id', $this->id)
            ->whereDate('end_dt', '>=', $start)
            ->whereDate('start_dt', '<=', $end);

        $tradePortfolios = $query->get();
        return $tradePortfolios;
    }

    public function maxCashBetween($start, $end): float
    {
        $cash = AssetExt::getCashAsset();
        $portfolioAssetsRepo = \App::make(PortfolioAssetRepository::class);
        $query = $portfolioAssetsRepo->makeModel()->newQuery()
            ->where('portfolio_id', $this->id)
            ->where('asset_id', $cash->id)
            ->whereDate('end_dt', '>', $start)
            ->whereDate('start_dt', '<', $end);

        $max = $query->max('position');
        Log::debug("max cash: ".json_encode([$this->id, $cash->id, $max]));
        if ($max == null) $max = 0.0;

        return $max;
    }

    public function assetHistory($assetId): Collection
    {
        $portfolioAssetsRepo = \App::make(PortfolioAssetRepository::class);
        $query = $portfolioAssetsRepo->makeModel()->newQuery()
            ->where('portfolio_id', $this->id)
            ->where('asset_id', $assetId);
        $portfolioAssets = $query->get();
        return $portfolioAssets;
    }

    /**
     * Get the set balance for a specific date, if one exists.
     * Uses start_dt <= date AND end_dt > date (end_dt is exclusive).
     *
     * @param string $date
     * @return PortfolioBalance|null
     */
    public function balanceAsOf($date)
    {
        return $this->portfolioBalances()
            ->whereDate('start_dt', '<=', $date)
            ->whereDate('end_dt', '>', $date)
            ->first();
    }

    /**
     * Calculate portfolio value from assets (positions * prices).
     *
     * @param string $now
     * @return float
     */
    public function calculateValueFromAssets($now): float
    {
        $portfolioAssets = $this->assetsAsOf($now);

        $totalValue = 0;
        foreach ($portfolioAssets as $pa) {
            if ($this->verbose) Log::debug("pa: " . json_encode($pa));
            $position = $pa->position;
            $asset_id = $pa->asset_id;
            if ($position == 0)
                continue;

            $asset = AssetExt::find($asset_id);
            if ($asset == null) {
                throw new Exception("Cant find asset $asset_id");
            }
            $assetPrice = $asset->priceAsOf($now);

            if (count($assetPrice) == 1) {
                $price = $assetPrice[0]['price'];
                $value = $position * $price;
                $totalValue += $value;
                if ($this->verbose) Log::debug("values: ".json_encode([$asset_id, $position, $price, $value]));
            } else {
                # TODO printf("No price for $asset_id\n");
            }
        }
        return $totalValue;
    }

    /**
     * Get portfolio value as of a date.
     * Uses set balance if available, otherwise calculates from assets.
     *
     * @param string $now Date string
     * @param bool $validate If true, returns validation info comparing set vs calculated
     * @return float|array Float value normally, or array with validation info if $validate=true
     */
    public function valueAsOf($now, bool $validate = false)
    {
        $setBalance = $this->balanceAsOf($now);
        $setBalanceValue = $setBalance ? (float) $setBalance->balance : null;

        if ($validate) {
            $calculatedValue = $this->calculateValueFromAssets($now);
            $difference = $setBalanceValue !== null ? $setBalanceValue - $calculatedValue : null;
            $percentDiff = ($setBalanceValue !== null && $calculatedValue > 0)
                ? (($setBalanceValue - $calculatedValue) / $calculatedValue) * 100
                : null;

            return [
                'value' => $setBalanceValue ?? $calculatedValue,
                'set_balance' => $setBalanceValue,
                'calculated' => $calculatedValue,
                'difference' => $difference,
                'percent_diff' => $percentDiff,
                'has_set_balance' => $setBalanceValue !== null,
                'is_valid' => $difference === null || abs($percentDiff) < 5.0, // 5% threshold
            ];
        }

        // Normal mode: return set balance if available, otherwise calculated
        if ($setBalanceValue !== null) {
            $this->debug('id '.$this->id);
            $this->debug('asOf '.$now);
            $this->debug('using set balance '.$setBalanceValue);
            return $setBalanceValue;
        }

        $totalValue = $this->calculateValueFromAssets($now);

        $this->debug('id '.$this->id);
        $this->debug('asOf '.$now);
        $this->debug('totalvalue '.$totalValue);
        return $totalValue;
    }

    /**
     * Calculate period performance using share price growth
     * This excludes the impact of deposits/withdrawals because share price = NAV / total_shares
     * When deposits come in, new shares are issued, so share price reflects only market performance
     */
    public function periodPerformance($from, $to)
    {
        $this->debug("periodPerformance $from $to");

        /** @var FundExt $fund */
        $fund = $this->fund()->first();
        if (!$fund) {
            // Fallback to simple calculation if no fund
            $valueFrom = $this->valueAsOf($from);
            $valueTo = $this->valueAsOf($to);
            if ($valueFrom == 0) return 0;
            return $valueTo / $valueFrom - 1;
        }

        // Use share price growth - this naturally excludes deposits/withdrawals
        $sharePriceFrom = $fund->shareValueAsOf($from);
        $sharePriceTo = $fund->shareValueAsOf($to);

        if ($sharePriceFrom == 0) return 0;
        return $sharePriceTo / $sharePriceFrom - 1;
    }

    public function yearlyPerformance($year)
    {
        $from = $year.'-01-01';
        $to = ($year+1).'-01-01';
        return $this->periodPerformance($from, $to);
    }

    /**
     * Validate all portfolio balances against calculated values.
     *
     * @param string|null $asOf Date to validate (defaults to today)
     * @param float $threshold Percentage difference threshold (default 5%)
     * @return array Validation results for all portfolios with set balances
     */
    public static function validateAllBalances(?string $asOf = null, float $threshold = 5.0): array
    {
        $asOf = $asOf ?? date('Y-m-d');
        $results = [];
        $hasErrors = false;

        // Get all portfolios that have balances for the given date
        $portfolioIds = PortfolioBalance::whereDate('start_dt', '<=', $asOf)
            ->whereDate('end_dt', '>', $asOf)
            ->pluck('portfolio_id')
            ->unique();

        foreach ($portfolioIds as $portfolioId) {
            $portfolio = self::find($portfolioId);
            if (!$portfolio) continue;

            $validation = $portfolio->valueAsOf($asOf, true);
            $validation['portfolio_id'] = $portfolioId;
            $validation['source'] = $portfolio->source;
            $validation['display_name'] = $portfolio->display_name;
            $validation['fund_name'] = $portfolio->fund?->name;

            // Check against threshold
            if ($validation['percent_diff'] !== null && abs($validation['percent_diff']) >= $threshold) {
                $validation['is_valid'] = false;
                $hasErrors = true;
            }

            $results[] = $validation;
        }

        return [
            'as_of' => $asOf,
            'threshold' => $threshold,
            'has_errors' => $hasErrors,
            'portfolios' => $results,
        ];
    }
}
