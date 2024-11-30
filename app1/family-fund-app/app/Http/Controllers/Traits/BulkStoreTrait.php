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
            $symbol['source'] = $source;
            $input = array_intersect_key($symbol, array_flip((new AssetExt())->fillable));
            if ($this->verbose) Log::debug("input: " . json_encode($input));
            if (AssetExt::isCashInput($input)) {
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
        if ($this->verbose) Log::debug("insertHistorical: " . json_encode([$source, $assetId, $timestamp, $newValue, $field]));
        $asset = AssetExt::find($assetId);
        if ($asset == null) {
            throw new Exception("Invalid asset provided: " . $assetId);
        }
        $query = $this->getQuery($source, $asset, $timestamp);

        $ret = null;
        $create = true;
        $newEnd = null;
        if (!$query->isEmpty()) {
            $create = false;
            foreach ($query as $obj) {
                if ($this->verbose) Log::debug("past obj: " . json_encode($obj));
                $tsDiff = $timestamp->getTimestamp() - $obj->start_dt->getTimestamp();
                if ($this->verbose) Log::debug("ts: " . json_encode([$obj->start_dt, $timestamp, $tsDiff]));
                if ($tsDiff == 0 && $obj->$field != $newValue) {
                    // There could have been updates of that amt that were
                    // associated w this record, its not safe to change
                    $symbol = $asset->name;
                    if ($this->verbose) Log::debug("obj: " . json_encode($obj));
                    if ($this->verbose) Log::debug("asset: " . json_encode($asset));
                    throw new Exception("A '$symbol' record with this exact timestamp and different $field already exists");
                } else if ($obj->$field != $newValue) {
                    // value changed, lets end & create new
                    $newEnd = $obj->end_dt; // in case thats not the last record
                    if ($this->verbose) Log::debug("newend: " . json_encode($obj));
                    $obj->end_dt = $timestamp;
                    $obj->save();
                    $create = true;
                } else {
                    $ret = $obj;
                }
            }
        }
        if ($create) {
            // wanna create, but there may be an asset in the future
            $query = $this->getQuery($source, $asset, '9999-12-30');
            if (!$query->isEmpty()) {
                $create = false;
                foreach ($query as $obj) {
                    if ($this->verbose) Log::debug("future obj: " . json_encode($obj));
                    if ($obj->$field != $newValue) {
                        $newEnd = $obj->start_dt; // in case we are not the last record
                        if ($this->verbose) Log::debug("newEnd: " . json_encode($obj));
                        $create = true;
                    } else {
                        $ret = $obj;
                        $obj->start_dt = $timestamp;
                        $obj->save();
                    }
                }
            }
            if ($create) {
                $data = $this->getChildData($asset, $newValue, $timestamp, $newEnd, $field);
                if ($this->verbose) Log::debug("create child: " . json_encode($data));
                $ret = $this->createChild($data, $source);
            }
        }
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
