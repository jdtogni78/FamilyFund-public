<?php

namespace App\Models;

use App\Repositories\AssetPriceRepository;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class AssetExt
 * @package App\Models
 */
class AssetExt extends Asset
{
    public static $rules = [
        'name' => 'required|string|max:128',
        'type' => 'required|[a-zA-Z][a-zA-Z]+|max:20',
        'source' => 'required|[a-zA-Z][a-zA-Z]+|max:30',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    public static function isCashInput($input):bool {
        return $input['name'] == 'CASH' || $input['type'] == 'CSH';
    }

    public static function getSP500Asset(): AssetExt {
        $symbol = "SPY";
        $asset = AssetExt::
            where('name', $symbol)
            ->where('type', 'STK')
            ->get()->first();
        if ($asset == null) {
            throw new Exception("Cant find asset {$symbol}");
        }
        return $asset;
    }

    public static function getCashAsset(): AssetExt
    {
        $asset = AssetExt::
            where('name', 'CASH')
            ->orWhere('type', 'CSH')
            ->get()->first();
        if ($asset == null) {
            throw new Exception("Cant find asset CASH");
        }
        return $asset;
    }

    public function isCash():bool {
        return $this->name == 'CASH' || $this->type == 'CSH';
    }

    /**
     **/
    public function pricesAsOf($now, $debug=false)
    {
        $assetPricesRepo = \App::make(AssetPriceRepository::class);
        $query = $assetPricesRepo->makeModel()->newQuery();
        $query->where('asset_id', $this->id)
            ->whereDate('start_dt', '<=', $now)
            ->whereDate('end_dt', '>', $now);

        $assetPrices = $query->get();
        if ($debug) {
            Log::debug($query->toSql());
            // log all query parameters
            foreach ($query->getBindings() as $i => $binding) {
                Log::debug("Binding $i is $binding");
            }
        }
        if ($assetPrices->count() > 1) {
            print_r($assetPrices->toArray());
            throw new \Exception("There should only be one asset price (found " . $assetPrices->count() . ") for asset " . $this->id . ' at ' . $now);
        }
        return $assetPrices;
    }
}
