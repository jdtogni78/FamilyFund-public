<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\API\TradePortfolioAPIController;
use App\Models\TradePortfolio;
use App\Models\Utils;
use App\Repositories\TradePortfolioRepository;
use App\Http\Resources\TradePortfolioResource;
use App\Http\Resources\TradePortfolioItemResource;
use Response;

/**
 * Class TradePortfolioController
 * @package App\Http\Controllers\API
 */

class TradePortfolioAPIControllerExt extends TradePortfolioAPIController
{
    public function __construct(TradePortfolioRepository $tradePortfolioRepo)
    {
        parent::__construct($tradePortfolioRepo);
    }

    /**
     * Display the specified TradePortfolio.
     * GET|HEAD /tradePortfolios/{accountName}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($accountName)
    {
        /** @var TradePortfolio $tradePortfolio */
        $tradePortfolio = $this->tradePortfolioRepository->all()->firstWhere('account_name', $accountName);

        if (empty($tradePortfolio)) {
            return $this->sendError('Trade Portfolio not found');
        }

        $arr = $this->createTradePortfolioResponse($tradePortfolio);

        return $this->sendResponse($arr, 'Trade Portfolio retrieved successfully');
    }

    private function createTradePortfolioResponse(TradePortfolio $tradePortfolio)
    {
        $rss = new TradePortfolioResource($tradePortfolio);
        $ret = $rss->toArray(NULL);

        $ret['items'] = array();
        $items = $tradePortfolio->tradePortfolioItems()->get();
        foreach ($items as $tpi) {
            $rss = new TradePortfolioItemResource($tpi);
            $item = $rss->toArray(NULL);
            unset($item['updated_at']);
            unset($item['created_at']);
            unset($item['trade_portfolio_id']);
            $ret['items'][] = $item;
        }

        return $ret;
    }

}
