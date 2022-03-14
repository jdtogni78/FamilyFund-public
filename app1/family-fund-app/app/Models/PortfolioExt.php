<?php

namespace App\Models;

use Eloquent as Model;
use App\Models\Portfolio;
use App\Models\PortfolioAsset;
use App\Models\AssetExt;
use App\Models\Utils;
use App\Repositories\PortfolioRepository;
use App\Repositories\PortfolioAssetRepository;
use DB;
use phpDocumentor\Reflection\Types\Collection;

/**
 * Class PortfolioExt
 * @package App\Models
 */
class PortfolioExt extends Portfolio
{
    /**
     * @return money
     **/
    public function assetsAsOf($now, $assetId=null)
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

    /**
     * @return Collection
     **/
    public function assetHistory($assetId)
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
    public function valueAsOf($now, bool $verbose=false): float
    {
        $portfolioAssets = $this->assetsAsOf($now);

        $totalValue = 0;
        foreach ($portfolioAssets as $pa) {
//            print_r("pa: " . json_encode($pa) . "\n");
            $position = $pa->position;
            $asset_id = $pa->asset_id;
            if ($position == 0)
                continue;

            $asset = AssetExt::findOrFail($asset_id);
            $assetPrice = $asset->pricesAsOf($now);

            if (count($assetPrice) == 1) {
                $price = $assetPrice[0]['price'];
                $value = $position * $price;
                $totalValue += $value;
                if ($verbose)
                    print_r("values: ".json_encode([$asset_id, $position, $price, $value])."\n");
            } else {
                # TODO printf("No price for $asset_id\n");
            }
        }
        // $totalValue = round($totalValue,4);
        if ($verbose) {
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
