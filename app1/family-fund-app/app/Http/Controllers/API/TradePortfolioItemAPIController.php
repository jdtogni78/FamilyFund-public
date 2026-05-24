<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Traits\AuthorizesApiAccess;
use App\Http\Requests\API\CreateTradePortfolioItemAPIRequest;
use App\Http\Requests\API\UpdateTradePortfolioItemAPIRequest;
use App\Models\TradePortfolio;
use App\Models\TradePortfolioItem;
use App\Repositories\TradePortfolioItemRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\TradePortfolioItemResource;
use Response;

/**
 * Class TradePortfolioItemController
 * @package App\Http\Controllers\API
 */

class TradePortfolioItemAPIController extends AppBaseController
{
    use AuthorizesApiAccess;

    /** @var  TradePortfolioItemRepository */
    private $tradePortfolioItemRepository;

    public function __construct(TradePortfolioItemRepository $tradePortfolioItemRepo)
    {
        $this->tradePortfolioItemRepository = $tradePortfolioItemRepo;
    }

    /**
     * Resolve the portfolio owning a trade portfolio item, via
     * item → tradePortfolio → portfolio, for object-level checks.
     */
    private function itemPortfolio(?TradePortfolioItem $item): ?\App\Models\Portfolio
    {
        return $item?->tradePortfolio?->portfolio;
    }

    /**
     * Display a listing of the TradePortfolioItem.
     * GET|HEAD /tradePortfolioItems
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $query = TradePortfolioItem::query();
        if (!$this->currentApiUser()?->isSystemAdmin()) {
            $query->whereHas('tradePortfolio', function ($tpq) {
                $tpq->whereHas('portfolio', function ($pq) {
                    $this->scopePortfolioQuery($pq);
                });
            });
        }

        foreach ($request->except(['skip', 'limit']) as $field => $value) {
            $query->where($field, $value);
        }

        if ($request->get('skip')) {
            $query->skip((int) $request->get('skip'));
        }

        if ($request->get('limit')) {
            $query->limit((int) $request->get('limit'));
        }

        $tradePortfolioItems = $query->get();

        return $this->sendResponse(TradePortfolioItemResource::collection($tradePortfolioItems), 'Trade Portfolio Items retrieved successfully');
    }

    /**
     * Store a newly created TradePortfolioItem in storage.
     * POST /tradePortfolioItems
     *
     * @param CreateTradePortfolioItemAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateTradePortfolioItemAPIRequest $request)
    {
        $input = $request->all();

        // Creating an item requires modify rights on the parent portfolio's fund.
        $tradePortfolio = TradePortfolio::find($input['trade_portfolio_id'] ?? null);
        $this->requirePortfolioAccess($tradePortfolio?->portfolio, modify: true);

        $tradePortfolioItem = $this->tradePortfolioItemRepository->create($input);

        return $this->sendResponse(new TradePortfolioItemResource($tradePortfolioItem), 'Trade Portfolio Item saved successfully');
    }

    /**
     * Display the specified TradePortfolioItem.
     * GET|HEAD /tradePortfolioItems/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var TradePortfolioItem $tradePortfolioItem */
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            return $this->sendError('Trade Portfolio Item not found');
        }

        $this->requirePortfolioAccess($this->itemPortfolio($tradePortfolioItem));

        return $this->sendResponse(new TradePortfolioItemResource($tradePortfolioItem), 'Trade Portfolio Item retrieved successfully');
    }

    /**
     * Update the specified TradePortfolioItem in storage.
     * PUT/PATCH /tradePortfolioItems/{id}
     *
     * @param int $id
     * @param UpdateTradePortfolioItemAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTradePortfolioItemAPIRequest $request)
    {
        $input = $request->all();

        /** @var TradePortfolioItem $tradePortfolioItem */
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            return $this->sendError('Trade Portfolio Item not found');
        }

        $this->requirePortfolioAccess($this->itemPortfolio($tradePortfolioItem), modify: true);

        $tradePortfolioItem = $this->tradePortfolioItemRepository->update($input, $id);

        return $this->sendResponse(new TradePortfolioItemResource($tradePortfolioItem), 'TradePortfolioItem updated successfully');
    }

    /**
     * Remove the specified TradePortfolioItem from storage.
     * DELETE /tradePortfolioItems/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var TradePortfolioItem $tradePortfolioItem */
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            return $this->sendError('Trade Portfolio Item not found');
        }

        $this->requirePortfolioAccess($this->itemPortfolio($tradePortfolioItem), modify: true);

        $tradePortfolioItem->delete();

        return $this->sendSuccess('Trade Portfolio Item deleted successfully');
    }
}
