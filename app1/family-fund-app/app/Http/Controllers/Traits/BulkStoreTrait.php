<?php

namespace App\Http\Controllers\Traits;

use App\Models\Asset;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use App\Models\PortfolioAsset;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Nette\Utils\DateTime;
use Exception;

trait BulkStoreTrait
{
    use VerboseTrait;
    public abstract function getQuery($source, $asset, $timestamp);
    protected abstract function createChild($data, $source);
    private $warnings;

    private function warn(string $string)
    {
        $this->warnings[] = $string;
    }

    public function genericBulkStore($request, $field)
    {
        $input = $request->all();
        $symbols = $request->collect('symbols')->toArray();
        $timestamp = new DateTime($request->timestamp);
        $source = $input['source'];

        foreach ($symbols as $symbol) {
            // Skip invalid prices (zero or negative) - indicates failed data fetch
            if ($field === 'price' && isset($symbol['price'])) {
                $price = floatval($symbol['price']);
                if ($price <= 0) {
                    $name = $symbol['name'] ?? 'unknown';
                    Log::warning("Skipping {$name} - invalid price: {$price}");
                    continue;
                }
            }

            $symbol['source'] = $source;
            $input = array_intersect_key($symbol, array_flip((new AssetExt())->fillable));
            if ($this->verbose) Log::debug("input: " . json_encode($input));
            if (AssetExt::isCashInput($input)) {
                // Validate cash symbols: if type is CSH, name must be CASH; if name is CASH, type must be CSH
                $isCashName = ($input['name'] ?? '') === 'CASH';
                $isCashType = ($input['type'] ?? '') === 'CSH';
                if ($isCashName !== $isCashType) {
                    throw new Exception("Invalid cash symbol: name='CASH' requires type='CSH' and vice versa");
                }
                unset($input['source']);
                $asset = Asset::orWhere($input)->firstOrFail();
            } else {
                $asset = AssetExt::firstOrCreate($input);
            }
            $this->insertHistorical($source, $asset->id, $timestamp, $symbol[$field], $field);
        }
    }

    /**
     * @throws Exception
     */
    public function insertHistorical($source, $assetId, $timestamp, $newValue, $field): PortfolioAsset|AssetPrice
    {
        if ($this->verbose) Log::info("=== insertHistorical START ===");
        if ($this->verbose) Log::info("Input: " . json_encode([
            'asset_id' => $assetId,
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'new_value' => $newValue,
            'field' => $field
        ]));

        $asset = AssetExt::find($assetId);
        if ($asset == null) {
            throw new Exception("Invalid asset provided: " . $assetId);
        }

        if ($this->verbose) Log::info("Asset: " . $asset->name . " (id: " . $asset->id . ")");

        $query = $this->getQuery($source, $asset, $timestamp);
        if ($this->verbose) Log::info("Query for existing records at timestamp returned: " . $query->count() . " records");

        $ret = null;
        $create = true;
        $newEnd = null;
        if (!$query->isEmpty()) {
            if ($this->verbose) Log::info("Found existing record(s) at timestamp - processing...");
            $create = false;
            foreach ($query as $obj) {
                if ($this->verbose) Log::info("Existing record: id=" . $obj->id . ", start=" . $obj->start_dt->format('Y-m-d') .
                    ", end=" . $obj->end_dt->format('Y-m-d') . ", " . $field . "=" . $obj->$field);

                $tsDiff = $timestamp->getTimestamp() - $obj->start_dt->getTimestamp();

                if ($tsDiff == 0 && $obj->$field != $newValue) {
                    // Exact timestamp with different value - throw error
                    $symbol = $asset->name;
                    if ($this->verbose) Log::error("CONFLICT: Exact timestamp exists with different $field!");
                    throw new Exception("A '$symbol' record with this exact timestamp and different $field already exists");
                } else if ($obj->$field != $newValue) {
                    // Value changed - split the range
                    if ($this->verbose) Log::info("VALUE CHANGED: Splitting range at " . $timestamp->format('Y-m-d'));
                    $newEnd = $obj->end_dt; // in case thats not the last record
                    $obj->end_dt = $timestamp;
                    $obj->save();
                    if ($this->verbose) Log::info("Updated record " . $obj->id . " end_dt to " . $timestamp->format('Y-m-d'));
                    $create = true;
                } else {
                    // Same value - keep existing record
                    if ($this->verbose) Log::info("SAME VALUE: Keeping existing record " . $obj->id);
                    $ret = $obj;
                }
            }
        } else {
            if ($this->verbose) Log::info("No existing record at timestamp - will check for future records");
        }
        if ($create) {
            if ($this->verbose) Log::info("Need to create new record - checking for future records...");

            // Find records that start AFTER this timestamp
            // BUG FIX: The old code used getQuery($source, $asset, '9999-12-30') which only finds records
            // active at that far-future date. This missed records ending before 9999-12-30.
            // We need to find ANY record starting after our current timestamp.
            $futureRecords = $asset->pricesStartingAfter($timestamp);

            if ($this->verbose) Log::info("Future records query returned: " . $futureRecords->count() . " records");

            if (!$futureRecords->isEmpty()) {
                $create = false;
                // Get the FIRST (earliest) future record
                $obj = $futureRecords->first();

                if ($this->verbose) Log::info("Future record: id=" . $obj->id . ", start=" . $obj->start_dt->format('Y-m-d') .
                    ", end=" . $obj->end_dt->format('Y-m-d') . ", " . $field . "=" . $obj->$field);

                if ($obj->$field != $newValue) {
                    // Future record has different value - create new record ending at future start
                    if ($this->verbose) Log::info("Future has different value - will create new record ending at " . $obj->start_dt->format('Y-m-d'));
                    $newEnd = $obj->start_dt;
                    $create = true;
                } else {
                    // Future record has same value - extend it backwards
                    if ($this->verbose) Log::info("Future has SAME value - extending backwards from " .
                        $obj->start_dt->format('Y-m-d') . " to " . $timestamp->format('Y-m-d'));
                    $ret = $obj;
                    $obj->start_dt = $timestamp;
                    $obj->save();
                    if ($this->verbose) Log::info("Updated record " . $obj->id . " start_dt to " . $timestamp->format('Y-m-d'));
                }
            } else {
                if ($this->verbose) Log::info("No future records - will create new record ending at 9999-12-31");
            }

            if ($create) {
                $data = $this->getChildData($asset, $newValue, $timestamp, $newEnd, $field);
                if ($this->verbose) Log::info("Creating new record: " . json_encode($data));
                $ret = $this->createChild($data, $source);
                if ($this->verbose) Log::info("Created new record with id: " . $ret->id);
            }
        }

        if ($this->verbose) Log::info("=== insertHistorical END ===");
        return $ret;
    }

    protected function getChildData($asset, $newValue, $timestamp, $endDt, $field): array
    {
        $data = [
            'asset_id' => $asset->id,
            $field => $newValue,
            'start_dt' => $timestamp,
        ];
        if ($endDt) $data['end_dt'] = $endDt;
        return $data;
    }

}
