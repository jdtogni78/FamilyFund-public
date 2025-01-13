<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\TradePortfolioItemController;
use Illuminate\Http\Request;
use Flash;
use Response;
use App\Models\AssetExt;
use App\Models\TradePortfolioExt;
use App\Models\TradePortfolioItemExt;

class TradePortfolioItemControllerExt extends TradePortfolioItemController
{

    public function edit($id)
    {
        $api = [];
        $api['assetMap'] = AssetExt::symbolMap();
        $api['portMap'] = TradePortfolioExt::portMap();
        $api['typeMap'] = TradePortfolioItemExt::typeMap();
        
        return parent::edit($id)->with('api', $api);
    }

    public function create()
    {
        $api = [];
        $api['assetMap'] = AssetExt::symbolMap();
        $api['portMap'] = TradePortfolioExt::portMap();
        $api['typeMap'] = TradePortfolioItemExt::typeMap();
        return view('trade_portfolio_items.create')
            ->with('api', $api);
    }

    public function createWithParams(Request $request)
    {
        $tradePortfolioId = $request->input('tradePortfolioId');
        $api = [];
        $api['tradePortfolioId'] = $tradePortfolioId;
        $api['assetMap'] = AssetExt::symbolMap();
        $api['portMap'] = TradePortfolioExt::portMap();
        $api['typeMap'] = TradePortfolioItemExt::typeMap();
        return view('trade_portfolio_items.create')
            ->with('api', $api);
    }
}
