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
        $arr['source'] = $portfolio->source;
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
            $asset['type'] = $a->type;
            $assetPrices = $a->priceAsOf($as_of);

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
}
