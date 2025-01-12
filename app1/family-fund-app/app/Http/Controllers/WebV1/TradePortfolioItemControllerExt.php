<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTradePortfolioItemRequest;
use App\Http\Requests\UpdateTradePortfolioItemRequest;
use App\Repositories\TradePortfolioItemRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;
use App\Models\AssetExt;
use App\Models\TradePortfolioExt;
use App\Models\TradePortfolioItemExt;

class TradePortfolioItemControllerExt extends TradePortfolioItemController
{

    public function create()
    {
        $api = [];
        $api['assetMap'] = AssetExt::assetMap();
        $api['portMap'] = TradePortfolioExt::portMap();
        $api['typeMap'] = TradePortfolioItemExt::typeMap();
        return view('trade_portfolio_items.create')
            ->with('api', $api);
    }
}
