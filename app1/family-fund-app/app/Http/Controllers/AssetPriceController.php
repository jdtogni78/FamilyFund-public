<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAssetPriceRequest;
use App\Http\Requests\UpdateAssetPriceRequest;
use App\Repositories\AssetPriceRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class AssetPriceController extends AppBaseController
{
    /** @var  AssetPriceRepository */
    protected $assetPriceRepository;

    public function __construct(AssetPriceRepository $assetPriceRepo)
    {
        $this->assetPriceRepository = $assetPriceRepo;
    }

    /**
     * Display a listing of the AssetPrice.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $assetPrices = $this->assetPriceRepository->all();

        return view('asset_prices.index')
            ->with('assetPrices', $assetPrices);
    }

    /**
     * Show the form for creating a new AssetPrice.
     *
     * @return Response
     */
    public function create()
    {
        return view('asset_prices.create');
    }

    /**
     * Store a newly created AssetPrice in storage.
     *
     * @param CreateAssetPriceRequest $request
     *
     * @return Response
     */
    public function store(CreateAssetPriceRequest $request)
    {
        $input = $request->all();

        $assetPrice = $this->assetPriceRepository->create($input);

        Flash::success('Asset Price saved successfully.');

        return redirect(route('assetPrices.index'));
    }

    /**
     * Display the specified AssetPrice.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $assetPrice = $this->assetPriceRepository->find($id);

        if (empty($assetPrice)) {
            Flash::error('Asset Price not found');

            return redirect(route('assetPrices.index'));
        }

        return view('asset_prices.show')->with('assetPrice', $assetPrice);
    }

    /**
     * Show the form for editing the specified AssetPrice.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $assetPrice = $this->assetPriceRepository->find($id);

        if (empty($assetPrice)) {
            Flash::error('Asset Price not found');

            return redirect(route('assetPrices.index'));
        }

        return view('asset_prices.edit')->with('assetPrice', $assetPrice);
    }

    /**
     * Update the specified AssetPrice in storage.
     *
     * @param int $id
     * @param UpdateAssetPriceRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAssetPriceRequest $request)
    {
        $assetPrice = $this->assetPriceRepository->find($id);

        if (empty($assetPrice)) {
            Flash::error('Asset Price not found');

            return redirect(route('assetPrices.index'));
        }

        $assetPrice = $this->assetPriceRepository->update($request->all(), $id);

        Flash::success('Asset Price updated successfully.');

        return redirect(route('assetPrices.index'));
    }

    /**
     * Remove the specified AssetPrice from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $assetPrice = $this->assetPriceRepository->find($id);

        if (empty($assetPrice)) {
            Flash::error('Asset Price not found');

            return redirect(route('assetPrices.index'));
        }

        $this->assetPriceRepository->delete($id);

        Flash::success('Asset Price deleted successfully.');

        return redirect(route('assetPrices.index'));
    }
}
