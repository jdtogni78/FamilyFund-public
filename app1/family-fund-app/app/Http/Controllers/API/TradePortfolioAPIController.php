<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateTradePortfolioAPIRequest;
use App\Http\Requests\API\UpdateTradePortfolioAPIRequest;
use App\Models\TradePortfolio;
use App\Repositories\TradePortfolioRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\TradePortfolioResource;
use Response;

/**
 * Class TradePortfolioController
 * @package App\Http\Controllers\API
 */

class TradePortfolioAPIController extends AppBaseController
{
    /** @var  TradePortfolioRepository */
    protected TradePortfolioRepository $tradePortfolioRepository;

    public function __construct(TradePortfolioRepository $tradePortfolioRepo)
    {
        $this->tradePortfolioRepository = $tradePortfolioRepo;
    }

    /**
     * Display a listing of the TradePortfolio.
     * GET|HEAD /tradePortfolios
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $tradePortfolios = $this->tradePortfolioRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(TradePortfolioResource::collection($tradePortfolios), 'Trade Portfolios retrieved successfully');
    }

    /**
     * Store a newly created TradePortfolio in storage.
     * POST /tradePortfolios
     *
     * @param CreateTradePortfolioAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateTradePortfolioAPIRequest $request)
    {
        $input = $request->all();

        $tradePortfolio = $this->tradePortfolioRepository->create($input);

        return $this->sendResponse(new TradePortfolioResource($tradePortfolio), 'Trade Portfolio saved successfully');
    }

    /**
     * Display the specified TradePortfolio.
     * GET|HEAD /tradePortfolios/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var TradePortfolio $tradePortfolio */
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            return $this->sendError('Trade Portfolio not found');
        }

        return $this->sendResponse(new TradePortfolioResource($tradePortfolio), 'Trade Portfolio retrieved successfully');
    }

    /**
     * Update the specified TradePortfolio in storage.
     * PUT/PATCH /tradePortfolios/{id}
     *
     * @param int $id
     * @param UpdateTradePortfolioAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTradePortfolioAPIRequest $request)
    {
        $input = $request->all();

        /** @var TradePortfolio $tradePortfolio */
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            return $this->sendError('Trade Portfolio not found');
        }

        $tradePortfolio = $this->tradePortfolioRepository->update($input, $id);

        return $this->sendResponse(new TradePortfolioResource($tradePortfolio), 'TradePortfolio updated successfully');
    }

    /**
     * Remove the specified TradePortfolio from storage.
     * DELETE /tradePortfolios/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var TradePortfolio $tradePortfolio */
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            return $this->sendError('Trade Portfolio not found');
        }

        $tradePortfolio->delete();

        return $this->sendSuccess('Trade Portfolio deleted successfully');
    }
}
