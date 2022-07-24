<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTradePortfolioItemRequest;
use App\Http\Requests\UpdateTradePortfolioItemRequest;
use App\Repositories\TradePortfolioItemRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class TradePortfolioItemController extends AppBaseController
{
    /** @var TradePortfolioItemRepository $tradePortfolioItemRepository*/
    protected $tradePortfolioItemRepository;

    public function __construct(TradePortfolioItemRepository $tradePortfolioItemRepo)
    {
        $this->tradePortfolioItemRepository = $tradePortfolioItemRepo;
    }

    /**
     * Display a listing of the TradePortfolioItem.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $tradePortfolioItems = $this->tradePortfolioItemRepository->all();

        return view('trade_portfolio_items.index')
            ->with('tradePortfolioItems', $tradePortfolioItems);
    }

    /**
     * Show the form for creating a new TradePortfolioItem.
     *
     * @return Response
     */
    public function create()
    {
        return view('trade_portfolio_items.create');
    }

    /**
     * Store a newly created TradePortfolioItem in storage.
     *
     * @param CreateTradePortfolioItemRequest $request
     *
     * @return Response
     */
    public function store(CreateTradePortfolioItemRequest $request)
    {
        $input = $request->all();

        $tradePortfolioItem = $this->tradePortfolioItemRepository->create($input);

        Flash::success('Trade Portfolio Item saved successfully.');

        return redirect(route('tradePortfolioItems.index'));
    }

    /**
     * Display the specified TradePortfolioItem.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            Flash::error('Trade Portfolio Item not found');

            return redirect(route('tradePortfolioItems.index'));
        }

        return view('trade_portfolio_items.show')->with('tradePortfolioItem', $tradePortfolioItem);
    }

    /**
     * Show the form for editing the specified TradePortfolioItem.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            Flash::error('Trade Portfolio Item not found');

            return redirect(route('tradePortfolioItems.index'));
        }

        return view('trade_portfolio_items.edit')->with('tradePortfolioItem', $tradePortfolioItem);
    }

    /**
     * Update the specified TradePortfolioItem in storage.
     *
     * @param int $id
     * @param UpdateTradePortfolioItemRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTradePortfolioItemRequest $request)
    {
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            Flash::error('Trade Portfolio Item not found');

            return redirect(route('tradePortfolioItems.index'));
        }

        $tradePortfolioItem = $this->tradePortfolioItemRepository->update($request->all(), $id);

        Flash::success('Trade Portfolio Item updated successfully.');

        return redirect(route('tradePortfolioItems.index'));
    }

    /**
     * Remove the specified TradePortfolioItem from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            Flash::error('Trade Portfolio Item not found');

            return redirect(route('tradePortfolioItems.index'));
        }

        $this->tradePortfolioItemRepository->delete($id);

        Flash::success('Trade Portfolio Item deleted successfully.');

        return redirect(route('tradePortfolioItems.index'));
    }
}
