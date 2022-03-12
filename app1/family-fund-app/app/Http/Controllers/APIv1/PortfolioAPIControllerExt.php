<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Requests\API\AssetsUpdatePortfolioAPIRequest;

use App\Models\Utils;
use App\Models\Portfolio;
use App\Repositories\PortfolioRepository;
use App\Http\Resources\PortfolioResource;
use App\Http\Controllers\AppBaseController;
use App\Models\Asset;
use App\Models\AssetPrice;
use App\Models\PortfolioAsset;
use Response;
use DB;
use const null;

/**
 * Class PortfolioControllerExt
 * @package App\Http\Controllers\API
 */

class PortfolioAPIControllerExt extends AppBaseController
{
    protected $portfolioRepository;
    public function __construct(PortfolioRepository $portfolioRepo)
    {
        $this->portfolioRepository = $portfolioRepo;
    }

    public function createPortfolioResponse($portfolio, $as_of)
    {
        $arr = [];
        $arr['assets'] = $this->createAssetsResponse($portfolio, $as_of);
        $arr['total_value'] = Utils::currency($this->totalValue);
        return $arr;
    }

    public function createAssetsResponse($portfolio, $as_of)
    {
        $arr = array();
        $portfolioAssets = $portfolio->assetsAsOf($as_of);
        $this->totalValue = 0;
        foreach ($portfolioAssets as $pa) {
            $asset = array();
            $asset_id = $pa->asset_id;
            $position = $pa->position;

            if ($position == 0)
                continue;

            $asset['id'] = $asset_id;
            $asset['position'] = Utils::position($position);
            $a = $pa->asset()->first();
            $asset['name'] = $a->name;
            $assetPrices = $a->pricesAsOf($as_of);

            if (count($assetPrices) == 1) {
                $price = $assetPrices[0]['price'];
                $value = $position * $price;
                $this->totalValue += $value;
                $asset['price'] = Utils::currency($price);
                $asset['value'] = Utils::currency($value);
            } else {
                # TODO printf("No price for $asset_id\n");
            }
            $arr[] = $asset;
        }
        return $arr;
    }

    /**
     * Display the specified Portfolio.
     * GET|HEAD /portfolios/{id}/as_of/{date}
     *
     * @param int $id
     *
     * @return Response
     */
    public function showAsOf($id, $as_of)
    {
        /** @var Portfolio $portfolio */
        $portfolio = $this->portfolioRepository->find($id);

        if (empty($portfolio)) {
            return $this->sendError('Portfolio not found');
        }

        $rss = new PortfolioResource($portfolio);
        $arr = $rss->toArray(NULL);
        $arr['assets'] = $this->createAssetsResponse($portfolio, $as_of);
        $arr['total_value'] = Utils::currency($this->totalValue);
        $arr['as_of'] = $as_of;

        return $this->sendResponse($arr, 'Portfolio retrieved successfully');
    }

    /**
     * Display the specified Portfolio.
     * GET|HEAD /portfolios/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $now = date('Y-m-d');
        return $this->showAsOf($id, $now);
    }

    /**
     * Update the specified Portfolio in storage.
     * PUT/PATCH /portfolios/{code}/update_assets
     *
     * @param int $code
     * @param AssetsUpdatePortfolioAPIRequest $request
     *
     * @return Response
     */
    public $timestamp = '';
    public $mode = true;
    public function assetsUpdate($code, AssetsUpdatePortfolioAPIRequest $request)
    {
        $input = $request->all();
        $data['assets'] = array();
        $data['asset_price'] = array();
        $data['portfolio'] = array();
        $data['portfolio_assets'] = array();
        $data['cash_asset'] = array();
        foreach ($input as $key => $value) {

            // $timestamp = '';
            if ($key == 'timestamp') {
                $timestamp = $value;
            }
            if ($key == 'mode') {
                if ($value == 'positions') {
        } else {
                    $this->mode = false;
        }
            }
            if ($key == 'symbols') {

                //positions mode
                if ($this->mode) {
                    foreach ($value as $s_key => $s_value) {
                        // dd($value);
                        if ($s_key == 'CASH') {
                            foreach ($s_value as $_key => $_value) {
                                if ($_key == 'price') {
                                    return $this->sendError('CASH never has price');
                                } else {
                                    if ($_key == 'position') {

                                        $cashRecord = Asset::where('name', 'CASH')->first();

                                        if (!empty($cashRecord)) {
                                            //if record found of CASH
                                            $assetId = $cashRecord->id;

                                            $portfolioData = Portfolio::where('code', $code)->first();
                                            if (!empty($portfolioData)) {
                                                $portfolioId = $portfolioData->id;
                                            } else {
                                                return $this->sendError('Code not Found');
                                            }
                                            $portfolioAssetsData = PortfolioAsset::where('asset_id', $assetId)
                                                ->where('portfolio_id', $portfolioId)
                                                ->where(function ($q) use ($timestamp) {
                                                    $q->where('start_dt', '>=', $timestamp);
                                                    $q->orWhere('end_dt', '<=', $timestamp);
                                                })->get();
                                            if ($portfolioAssetsData->isEmpty()) {
                                                $portfolioAsset = PortfolioAsset::create([
                                                    'portfolio_id' => $portfolioId,
                                                    'asset_id' => $assetId,
                                                    'position' => $_value,

                                                    'start_dt' => $timestamp,
                                                    'end_dt' => '9999-12-31'
                                                ]);
                                                array_push($data['portfolio_assets'], $portfolioAsset);
                                            } else {
                                                foreach ($portfolioAssetsData  as $pad) {
                                                    if ($pad->position == $_value) {
                                                        array_push($data['portfolio_assets'], $pad);
                                                    } else {


                                                        $portfolioAssetData = PortfolioAsset::create([
                                                            'portfolio_id' => $pad->portfolio_id,
                                                            'asset_id' => $pad->asset_id,
                                                            'position' => $_value,
                                                            'start_dt' => $timestamp,
                                                            'end_dt'   => $pad->end_dt
                                                        ]);
                                                        array_push($data['portfolio_assets'], $portfolioAssetData);
                                                        $pad->update([
                                                            'end_dt' => $timestamp,
                                                        ]);
                                                        array_push($data['portfolio_assets'], $pad);
                                                    }
                                                }
                                            }
                                        } else {
                                            return $this->sendError('NO CASH Record Found');
                                        }
                                    }
                                }
                            }
                            $cashRecord = Asset::where('name', 'CASH')->first();
                            if (!empty($cashRecord)) {
                                array_push($data['cash_asset'], $cashRecord);
                            }
                        } else {
                            $assetData = Asset::where('feed_id', $s_key)->where('source_feed', $code)->first();
                            if (!empty($assetData)) {

                                $assetId = $assetData->id;
                                $updateType = 'Unknown';
                                $updatePrice = '0.00';
                                $updatePosition = '';

                                foreach ($s_value as $_key => $_value) {
                                    if ($_key == 'price') {
                                        $updatePrice = $_value;
                                    }
                                    if ($_key == 'type') {
                                        $updateType = $_value;
                                    }
                                    if ($_key == 'position') {
                                        $updatePosition = $_value;
                                    }
                                }
                                // dd($updatePosition);
                                if ($assetData->type == $updateType) {
                                    array_push($data['assets'], $assetData);
                                } else {
                                    $newAssetData = Asset::create(
                                        [
                                            'name'     => $s_key,
                                            'feed_id'     => $s_key,
                                            'source_feed'     => $code,
                                            'type'     => $updateType,
                                        ]
                                    );
                                    array_push($data['assets'], $newAssetData);
                                }

                                $assetPrices = AssetPrice::where('asset_id', $assetId)->get();
                                if ($assetPrices->isEmpty()) {

                                    $assetPrices =  AssetPrice::create([
                                        'asset_id' => $assetId,
                                        'price'    => $updatePrice,
                                        'start_dt' => $timestamp,
                                        'end_dt'   => '9999-12-31'
                                    ]);

                                    array_push($data['asset_price'], $assetPrices);
                                } else {
                                    foreach ($assetPrices as $priceValue) {
                                        if ($priceValue->price == $updatePrice) {
                                            array_push($data['asset_price'], $priceValue);
                                        } else {
                                            $assetPrices = AssetPrice::create([
                                                'asset_id' => $assetId,
                                                'price'    => $updatePrice,
                                                'start_dt' => $timestamp,
                                                'end_dt'   => $priceValue->end_dt
                                            ]);
                                            array_push($data['asset_price'], $assetPrices);

                                            $priceValue->update(['end_dt' => $timestamp]);
                                        }
                                    }
                                }
                                $portfolioData = Portfolio::where('code', $code)->first();
                                if (!empty($portfolioData)) {
                                    array_push($data['portfolio'], $portfolioData);
                                    $portfolioId = $portfolioData->id;
                                } else {
                                    return $this->sendError('Code not Found');
                                }
                                $portfolioAssetsData = PortfolioAsset::where('asset_id', $assetId)->where(function ($q) use ($timestamp) {
                                    $q->where('start_dt', '>=', $timestamp);
                                    $q->orWhere('end_dt', '<=', $timestamp);
                                })->get();
                                if ($portfolioAssetsData->isEmpty()) {

                                    $portfolioAssets = PortfolioAsset::create([
                                        'portfolio_id' => $portfolioId,
                                        'asset_id' => $assetId,
                                        'position' => $updatePosition,
                                        'start_dt' => $timestamp,
                                        'end_dt' => '9999-12-31'
                                    ]);
                                    array_push($data['portfolio_assets'], $portfolioAssets);
                                } else {
                                    foreach ($portfolioAssetsData  as $pad) {
                                        if ($pad->position == $_value) {
                                            array_push($data['portfolio_assets'], $pad);
                                        } else {

                                            $portfolioAssetData = PortfolioAsset::create([
                                                'portfolio_id' => $pad->portfolio_id,
                                                'asset_id' => $pad->asset_id,
                                                'position' => $updatePosition,
                                                'start_dt' => $timestamp,
                                                'end_dt'   => $pad->end_dt
                                            ]);
                                            // dd($portfolioAssetData);
                                            array_push($data['portfolio_assets'], $portfolioAssetData);

                                            $pad->update([
                                                'end_dt' => $timestamp,
                                            ]);
                                            array_push($data['portfolio_assets'], $pad);
                                        }
                                    }
                                }
                            } else {
                                $portfolioData = Portfolio::where('code', $code)->first();
                                if (!empty($portfolioData)) {
                                    array_push($data['portfolio'], $portfolioData);

                                    $typeValue = 'Unknown';
                                    $priceValue = '0.00';
                                    $positionValue = '0.00';
                                    // dd($positionValue);
                                    foreach ($s_value as $_key => $_value) {
                                        if ($_key == 'type') {
                                            $typeValue = $_value;
                                        } else if ($_key == 'price') {
                                            $priceValue = $_value;
                                        } else if ($_key == 'position') {
                                            $positionValue = $_value;
                                        }
                                    }
                                    $newAssetData = Asset::create(
                                        [
                                            'name'     => $s_key,
                                            'feed_id'     => $s_key,
                                            'source_feed'     => $code,
                                            'type'     => $typeValue,
                                        ]
                                    );
                                    array_push($data['assets'], $newAssetData);

                                    $newAssetPriceData = AssetPrice::create(
                                        [
                                            'asset_id'     => $newAssetData->id,
                                            'price'     => $priceValue,
                                            'start_dt'     => $timestamp,
                                            'end_dt'     => '9999-12-31',
                                        ]
                                    );
                                    array_push($data['asset_price'], $newAssetPriceData);


                                    $porfolioAssetsData = PortfolioAsset::create([
                                        'portfolio_id'  => $portfolioData->id,
                                        'asset_id'      => $newAssetData->id,
                                        'position'      => $positionValue,
                                        'start_dt'      => $timestamp,
                                        'end_dt'        => '9999-12-31'
                                    ]);

                                    array_push($data['portfolio_assets'], $porfolioAssetsData);
                                } else {
                                    return $this->sendError('Code not Found');
                                }
                            }
                        }
                    }
                } else {
                    //price mode
                    foreach ($value as $s_key => $s_value) {
                        // dd($value);
                        if ($s_key == 'CASH') {
                            $cashRecord = Asset::where('name', 'CASH')->first();

                            foreach ($s_value as $_key => $_value) {
                                if ($_key == 'price') {
                                    return $this->sendError('CASH never has price');
                                } else {
                                    if ($_key == 'position') {
                                        return $this->sendError('Positions were found and ignored, use mode=positions (provided mode=price)');
                                    }
                                }
                            }
                            if (!empty($cashRecord)) {
                                array_push($data['cash_asset'], $cashRecord);
                            }
                        } else {
                            $assetData = Asset::where('feed_id', $s_key)->where('source_feed', $code)->first();
                            if (!empty($assetData)) {

                                $assetId = $assetData->id;
                                $updateType = 'Unknown';
                                $updatePrice = '0.00';
                                $updatePosition = '';

                                foreach ($s_value as $_key => $_value) {
                                    if ($_key == 'price') {
                                        $updatePrice = $_value;
                                    }
                                    if ($_key == 'type') {
                                        $updateType = $_value;
                                    }
                                    if ($_key == 'position') {
                                        return $this->sendError('Positions were found and ignored, use mode=positions (provided mode=price)');
                                    }
                                }

                                if ($assetData->type == $updateType) {
                                    array_push($data['assets'], $assetData);
                                } else {
                                    $newAssetData = Asset::create(
                                        [
                                            'name'     => $s_key,
                                            'feed_id'     => $s_key,
                                            'source_feed'     => $code,
                                            'type'     => $updateType,
                                        ]
                                    );
                                    array_push($data['assets'], $newAssetData);
                                }

                                $assetPrices = AssetPrice::where('asset_id', $assetId)->get();
                                if ($assetPrices->isEmpty()) {

                                    $assetPrices =  AssetPrice::create([
                                        'asset_id' => $assetId,
                                        'price'    => $updatePrice,
                                        'start_dt' => $timestamp,
                                        'end_dt'   => '9999-12-31'
                                    ]);
                                    array_push($data['asset_price'], $assetPrices);
                                } else {

                                    foreach ($assetPrices as $priceValue) {
                                        if ($priceValue->price == $updatePrice) {
                                            array_push($data['asset_price'], $priceValue);
                                        } else {
                                            $assetPrices = AssetPrice::create([
                                                'asset_id' => $assetId,
                                                'price'    => $updatePrice,
                                                'start_dt' => $timestamp,
                                                'end_dt'   => $priceValue->end_dt
                                            ]);
                                            array_push($data['asset_price'], $assetPrices);
                                            $priceValue->update(['end_dt' => $timestamp]);
                                        }
                                    }
                                }
                            } else {
                                $typeValue = 'Unknown';
                                $priceValue = '0.00';
                                foreach ($s_value as $_key => $_value) {
                                    if ($_key == 'type') {
                                        $typeValue = $_value;
                                    } else if ($_key == 'price') {
                                        $priceValue = $_value;
                                    }
                                }
                                $newAssetData = Asset::create(
                                    [
                                        'name'     => $s_key,
                                        'feed_id'     => $s_key,
                                        'source_feed'     => $code,
                                        'type'     => $typeValue,
                                    ]
                                );
                                array_push($data['assets'], $newAssetData);

                                $newAssetPriceData = AssetPrice::create(
                                    [
                                        'asset_id'     => $newAssetData->id,
                                        'price'     => $priceValue,
                                        'start_dt'     => $timestamp,
                                        'end_dt'     => '9999-12-31',
                                    ]
                                );
                                array_push($data['asset_price'], $newAssetPriceData);
                            }
                        }
                    }
                }
            }
        }







        return $this->sendResponse($data, 'Request completed successfully');
    }

    protected function getValue(array $input, $key, $default = null): mixed
    {
        if (array_key_exists($key, $input)) {
            $value = $input[$key];
        } else {
            $value = $default;
        }
        return $value;
    }
}
