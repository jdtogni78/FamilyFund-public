<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTradePortfolioRequest;
use App\Http\Requests\UpdateTradePortfolioRequest;
use App\Repositories\TradePortfolioRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class TradePortfolioController extends AppBaseController
{
    /** @var TradePortfolioRepository $tradePortfolioRepository*/
    protected $tradePortfolioRepository;

    public function __construct(TradePortfolioRepository $tradePortfolioRepo)
    {
        $this->tradePortfolioRepository = $tradePortfolioRepo;
    }

    /**
     * Display a listing of the TradePortfolio.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $tradePortfolios = $this->tradePortfolioRepository->all();

        return view('trade_portfolios.index')
            ->with('tradePortfolios', $tradePortfolios);
    }

    /**
     * Show the form for creating a new TradePortfolio.
     *
     * @return Response
     */
    public function create()
    {
        return view('trade_portfolios.create');
    }

    /**
     * Store a newly created TradePortfolio in storage.
     *
     * @param CreateTradePortfolioRequest $request
     *
     * @return Response
     */
    public function store(CreateTradePortfolioRequest $request)
    {
        $input = $request->all();

        $tradePortfolio = $this->tradePortfolioRepository->create($input);

        Flash::success('Trade Portfolio saved successfully.');

        return redirect(route('tradePortfolios.index'));
    }

    /**
     * Display the specified TradePortfolio.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            Flash::error('Trade Portfolio not found');

            return redirect(route('tradePortfolios.index'));
        }

        return view('trade_portfolios.show')->with('tradePortfolio', $tradePortfolio);
    }

    /**
     * Show the form for editing the specified TradePortfolio.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            Flash::error('Trade Portfolio not found');

            return redirect(route('tradePortfolios.index'));
        }

        return view('trade_portfolios.edit')->with('tradePortfolio', $tradePortfolio);
    }

    /**
     * Update the specified TradePortfolio in storage.
     *
     * @param int $id
     * @param UpdateTradePortfolioRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTradePortfolioRequest $request)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            Flash::error('Trade Portfolio not found');

            return redirect(route('tradePortfolios.index'));
        }

        $tradePortfolio = $this->tradePortfolioRepository->update($request->all(), $id);

        Flash::success('Trade Portfolio updated successfully.');

        return redirect(route('tradePortfolios.index'));
    }

    /**
     * Remove the specified TradePortfolio from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $tradePortfolio = $this->tradePortfolioRepository->find($id);

        if (empty($tradePortfolio)) {
            Flash::error('Trade Portfolio not found');

            return redirect(route('tradePortfolios.index'));
        }

        $this->tradePortfolioRepository->delete($id);

        Flash::success('Trade Portfolio deleted successfully.');

        return redirect(route('tradePortfolios.index'));
    }
}
