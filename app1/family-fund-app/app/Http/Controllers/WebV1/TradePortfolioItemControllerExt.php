<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateTradePortfolioItemRequest;
use App\Http\Requests\UpdateTradePortfolioItemRequest;
use App\Models\AssetExt;
use App\Models\TradePortfolioExt;
use App\Models\TradePortfolioItemExt;
use App\Repositories\TradePortfolioItemRepository;
use Illuminate\Http\Request;
use Flash;
use Response;

class TradePortfolioItemControllerExt extends AppBaseController
{
    /** @var TradePortfolioItemRepository $tradePortfolioItemRepository*/
    protected $tradePortfolioItemRepository;

    public function __construct(TradePortfolioItemRepository $tradePortfolioItemRepo)
    {
        $this->tradePortfolioItemRepository = $tradePortfolioItemRepo;
    }

    public function edit($id)
    {
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            Flash::error('Trade Portfolio Item not found');

            return redirect(route('tradePortfolioItems.index'));
        }

        $api = [];
        $api['assetMap'] = AssetExt::symbolMap();
        $api['portMap'] = TradePortfolioExt::portMap();
        $api['typeMap'] = TradePortfolioItemExt::typeMap();

        return view('trade_portfolio_items.edit')
            ->with('tradePortfolioItem', $tradePortfolioItem)
            ->with('api', $api);
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

    // --- inlined from former base ---

    public function index(Request $request)
    {
        // Defense-in-depth: scope to items whose trade portfolio's portfolio is
        // in a fund the user can access, in addition to fund.full middleware. (#85)
        $tradePortfolioItems = $this->authz()
            ->scopeByPortfolioRelation(TradePortfolioItemExt::query(), 'tradePortfolio.portfolio')
            ->get();

        return view('trade_portfolio_items.index')
            ->with('tradePortfolioItems', $tradePortfolioItems);
    }

    public function store(CreateTradePortfolioItemRequest $request)
    {
        $input = $request->all();

        $tradePortfolioItem = $this->tradePortfolioItemRepository->create($input);

        Flash::success('Trade Portfolio Item saved successfully.');

        return redirect(route('tradePortfolios.show', [$tradePortfolioItem->tradePortfolio()->first()->id]));
    }

    public function show($id)
    {
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            Flash::error('Trade Portfolio Item not found');

            return redirect(route('tradePortfolioItems.index'));
        }

        return view('trade_portfolio_items.show')->with('tradePortfolioItem', $tradePortfolioItem);
    }

    public function update($id, UpdateTradePortfolioItemRequest $request)
    {
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            Flash::error('Trade Portfolio Item not found');

            return redirect(route('tradePortfolioItems.index'));
        }

        $tradePortfolioItem = $this->tradePortfolioItemRepository->update($request->all(), $id);

        Flash::success('Trade Portfolio Item updated successfully.');

        return redirect(route('tradePortfolios.show', [$tradePortfolioItem->tradePortfolio()->first()->id]));
    }

    public function destroy($id)
    {
        $tradePortfolioItem = $this->tradePortfolioItemRepository->find($id);

        if (empty($tradePortfolioItem)) {
            Flash::error('Trade Portfolio Item not found');

            return redirect(route('tradePortfolioItems.index'));
        }

        $tpId = $tradePortfolioItem->tradePortfolio()->first()->id;
        $this->tradePortfolioItemRepository->delete($id);

        Flash::success('Trade Portfolio Item deleted successfully.');

        return redirect(route('tradePortfolios.show', [$tpId]));
    }
}
