<?php

namespace App\Models;

use App\Repositories\PortfolioAssetRepository;
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
    private mixed $verbose = false;

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
        Log::debug("max cash: ".json_encode([$this->id, $cash->id, $max])."\n");

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
     * @param $now
     * @param bool $verbose
     * @return float
     */
    public function valueAsOf($now): float
    {
        $portfolioAssets = $this->assetsAsOf($now);

        $totalValue = 0;
        foreach ($portfolioAssets as $pa) {
//            print_r("pa: " . json_encode($pa) . "\n");
            $position = $pa->position;
            $asset_id = $pa->asset_id;
            if ($position == 0)
                continue;

            $asset = AssetExt::find($asset_id);
            if ($asset == null) {
                throw new Exception("Cant find asset $asset_id");
            }
            $assetPrice = $asset->pricesAsOf($now);

            if (count($assetPrice) == 1) {
                $price = $assetPrice[0]['price'];
                $value = $position * $price;
                $totalValue += $value;
                if ($this->verbose) print_r("values: ".json_encode([$asset_id, $position, $price, $value])."\n");
            } else {
                # TODO printf("No price for $asset_id\n");
            }
        }
        // $totalValue = round($totalValue,4);
        if ($this->verbose) {
            print('id '.$this->id."\n");
            print('asOf '.$now."\n");
            print('totalvalue '.$totalValue."\n");
        }
        return $totalValue;
    }

    public function periodPerformance($from, $to)
    {
        $valueFrom = $this->valueAsOf($from);
        $valueTo = $this->valueAsOf($to);
        // var_dump(array($from, $to, $valueFrom, $valueTo));
        if ($valueFrom == 0) return 0;
        return $valueTo / $valueFrom - 1;
    }

    public function yearlyPerformance($year)
    {
        $from = $year.'-01-01';
        $to = ($year+1).'-01-01';
        return $this->periodPerformance($from, $to);
    }
}
