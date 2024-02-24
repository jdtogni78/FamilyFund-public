<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePortfolioAssetRequest;
use App\Http\Requests\UpdatePortfolioAssetRequest;
use App\Repositories\PortfolioAssetRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class PortfolioAssetController extends AppBaseController
{
    /** @var  PortfolioAssetRepository */
    protected $portfolioAssetRepository;

    public function __construct(PortfolioAssetRepository $portfolioAssetRepo)
    {
        $this->portfolioAssetRepository = $portfolioAssetRepo;
    }

    /**
     * Display a listing of the PortfolioAsset.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $name = $request->input("name");

        $portfolioAssets = $this->portfolioAssetRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit'));

        if (!empty($name))
            $portfolioAssets = $portfolioAssets->where('asset.name', '=', "$name");

        $portfolioAssets = $portfolioAssets
            ->sortBy('asset.name')
            ->sortByDesc('start_dt');

        return view('portfolio_assets.index')
            ->with('portfolioAssets', $portfolioAssets);
    }

    /**
     * Show the form for creating a new PortfolioAsset.
     *
     * @return Response
     */
    public function create()
    {
        return view('portfolio_assets.create');
    }

    /**
     * Store a newly created PortfolioAsset in storage.
     *
     * @param CreatePortfolioAssetRequest $request
     *
     * @return Response
     */
    public function store(CreatePortfolioAssetRequest $request)
    {
        $input = $request->all();

        $portfolioAsset = $this->portfolioAssetRepository->create($input);

        Flash::success('Portfolio Asset saved successfully.');

        return redirect(route('portfolioAssets.index'));
    }

    /**
     * Display the specified PortfolioAsset.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $portfolioAsset = $this->portfolioAssetRepository->find($id);

        if (empty($portfolioAsset)) {
            Flash::error('Portfolio Asset not found');

            return redirect(route('portfolioAssets.index'));
        }

        return view('portfolio_assets.show')->with('portfolioAsset', $portfolioAsset);
    }

    /**
     * Show the form for editing the specified PortfolioAsset.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $portfolioAsset = $this->portfolioAssetRepository->find($id);

        if (empty($portfolioAsset)) {
            Flash::error('Portfolio Asset not found');

            return redirect(route('portfolioAssets.index'));
        }

        return view('portfolio_assets.edit')->with('portfolioAsset', $portfolioAsset);
    }

    /**
     * Update the specified PortfolioAsset in storage.
     *
     * @param int $id
     * @param UpdatePortfolioAssetRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePortfolioAssetRequest $request)
    {
        $portfolioAsset = $this->portfolioAssetRepository->find($id);

        if (empty($portfolioAsset)) {
            Flash::error('Portfolio Asset not found');

            return redirect(route('portfolioAssets.index'));
        }

        $portfolioAsset = $this->portfolioAssetRepository->update($request->all(), $id);

        Flash::success('Portfolio Asset updated successfully.');

        return redirect(route('portfolioAssets.index'));
    }

    /**
     * Remove the specified PortfolioAsset from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $portfolioAsset = $this->portfolioAssetRepository->find($id);

        if (empty($portfolioAsset)) {
            Flash::error('Portfolio Asset not found');

            return redirect(route('portfolioAssets.index'));
        }

        $this->portfolioAssetRepository->delete($id);

        Flash::success('Portfolio Asset deleted successfully.');

        return redirect(route('portfolioAssets.index'));
    }
}
