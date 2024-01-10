<?php

namespace App\Http\Controllers\WebV1;

use App\Repositories\TradePortfolioRepository;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;
use Mockery\Exception;
use Response;
use App\Http\Controllers\TradePortfolioController;

class TradePortfolioControllerExt extends TradePortfolioController
{
    public function __construct(TradePortfolioRepository $tradePortfolioRepo)
    {
        parent::__construct($tradePortfolioRepo);
    }

    /**
     * Display the specified TradePorfolio.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        return $this->showAsOf($id, null);
    }

    /**
     * Display the specified Fund.
     *
     * @param int $id
     *
     * @return Response
     */
    public function showAsOf($id, $asOf=null)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            Flash::error('Trade Portfolio not found');

            return redirect(route('tradePortfolios.index'));
        }

        Log::info($tradePortfolio->tradePortfolioItems()->count()."\r");
        return view('trade_portfolios.show',
                [
                    'tradePortfolio' => $tradePortfolio,
                    'fund' => $tradePortfolio->fund()->first(),
                    'tradePortfolioItems' => $tradePortfolio->tradePortfolioItems()->get()
                ]);
    }
}
