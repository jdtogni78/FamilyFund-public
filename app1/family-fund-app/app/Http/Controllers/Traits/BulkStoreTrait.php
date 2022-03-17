<?php

namespace App\Http\Controllers\Traits;

use App\Models\Asset;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use App\Models\PortfolioAsset;
use Nette\Schema\ValidationException;

trait BulkStoreTrait
{
    protected abstract function getQuery($source, $asset, $timestamp);
    protected abstract function createChild($data, $source);
    private $verbose = false;
    private $warnings;

    private function warn(string $string)
    {
        $this->warnings[] = $string;
    }

    public function genericBulkStore($request, $field)
    {
        $input = $request->all();
        $symbols = $request->collect('symbols')->toArray();
        $timestamp = $input['timestamp'];
        $source = $input['source'];

        foreach ($symbols as $symbol) {
            $symbol['source'] = $source;
            $input = array_intersect_key($symbol, array_flip((new AssetExt())->fillable));
            if ($this->verbose) print_r("input: " . json_encode($input) . "\n");
            if (AssetExt::isCashInput($input)) {
                unset($input['source']);
                $asset = Asset::orWhere($input)->firstOrFail();
            } else {
                $asset = AssetExt::firstOrCreate($input);
            }
            $this->insertHistorical($source, $asset->id, $timestamp, $symbol[$field], $field);
        }
    }

    public function insertHistorical($source, $assetId, $timestamp, $newValue, $field): PortfolioAsset|AssetPrice
    {
        $asset = AssetExt::find($assetId);
        if ($asset == null) {
            throw new ValidationException("Invalid asset provided: " . $assetId);
        }
        $query = $this->getQuery($source, $asset, $timestamp);

        $ret = null;
        $create = true;
        $newEnd = null;
        if (!$query->isEmpty()) {
            $create = false;
            foreach ($query as $obj) {
                if ($this->verbose) print_r("past obj: " . json_encode($obj) . "\n");
                // value changed, lets end & create new
                if ($obj->$field != $newValue) {
                    $newEnd = $obj->end_dt; // in case thats not the last record
                    if ($this->verbose) print_r("newend: " . json_encode($obj) . "\n");
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
                    if ($this->verbose) print_r("future obj: " . json_encode($obj) . "\n");
                    if ($obj->$field != $newValue) {
                        $newEnd = $obj->start_dt; // in case we are not the last record
                        if ($this->verbose) print_r("newEnd: " . json_encode($obj) . "\n");
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
                if ($this->verbose) print_r("create child: " . json_encode($data) . "\n");
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
