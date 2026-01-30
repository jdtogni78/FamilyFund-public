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
        'source' => 'nullable|[a-zA-Z][a-zA-Z]+|max:30',
        'data_source' => 'required|string|max:30',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * Map a portfolio source to its data source.
     * Data sources represent where price data comes from (IB, MANUAL, etc.)
     * Portfolio sources are identifiers like 'FFIB', 'MONARCH_FIDE_XXXX', etc.
     */
    public static function getDataSourceForPortfolio(string $portfolioSource): string
    {
        // Cash is always manual
        if ($portfolioSource === 'CASH') {
            return 'MANUAL';
        }

        // IB sources: *IB, MONARCH_*
        if (str_ends_with($portfolioSource, 'IB') || str_starts_with($portfolioSource, 'MONARCH_')) {
            return 'IB';
        }

        // Default to IB for now
        return 'IB';
    }

    /**
     * Find or create an asset by data_source, name, and type.
     * This is the preferred method for looking up assets to avoid duplicates.
     */
    public static function findOrCreateByDataSource(string $name, string $type, string $dataSource, ?string $source = null): AssetExt
    {
        return self::firstOrCreate(
            [
                'data_source' => $dataSource,
                'name' => $name,
                'type' => $type,
            ],
            [
                'source' => $source, // Keep for backward compatibility
            ]
        );
    }

    public static function assetMap() {
        $assets = AssetExt::all();
        $map = ['none' => 'Select Asset'];
        foreach ($assets as $asset) {
            $map[$asset->id] = $asset->name;
        }
        return $map;
    }

    public static function symbolMap() {
        $assets = AssetExt::all();
        $map = ['none' => 'Select Asset'];
        foreach ($assets as $asset) {
            $map[$asset->name] = $asset->name;
        }
        return $map;
    }

    public static function isCashInput($input):bool {
        return $input['name'] == 'CASH' || $input['type'] == 'CSH';
    }

    public static function getSP500Asset(): AssetExt {
        $asset = self::getAsset("SPY", "STK");
        return $asset;
    }

    public static function getCashAsset(): AssetExt {
        $asset = self::getAsset("CASH", "CSH");
        return $asset;
    }

    public static function getAsset(string $symbol, string $type) : AssetExt {
        $asset = AssetExt::where('name', $symbol)
            ->where('type', $type)
            ->get()->first();
        if ($asset == null) {
            throw new Exception("Cant find asset {$symbol}");
        }
        return $asset;
    }

    public function isCash():bool {
        return $this->name == 'CASH' || $this->type == 'CSH';
    }

    /**
     **/
    public function priceAsOf($now, $debug=false)
    {
        $assetPricesRepo = \App::make(AssetPriceRepository::class);
        $query = $assetPricesRepo->makeModel()->newQuery();
        $query->where('asset_id', $this->id)
            ->whereDate('start_dt', '<=', $now)
            ->whereDate('end_dt', '>', $now);

        $assetPrices = $query->get();
        if ($assetPrices->count() > 1) {
            print_r($assetPrices->toArray());
            throw new \Exception("There should only be one asset price (found " . $assetPrices->count() . ") for asset " . $this->id . ' at ' . $now);
        }
        return $assetPrices;
    }

    /**
     * Find all price records that start AFTER the given timestamp.
     * Used by BulkStoreTrait to find future records when backfilling gaps.
     *
     * @param \DateTime|string $timestamp The timestamp to search after
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function pricesStartingAfter($timestamp)
    {
        $assetPricesRepo = \App::make(AssetPriceRepository::class);
        $query = $assetPricesRepo->makeModel()->newQuery();
        $query->where('asset_id', $this->id)
            ->where('start_dt', '>', $timestamp)
            ->orderBy('start_dt', 'asc');

        return $query->get();
    }
}
