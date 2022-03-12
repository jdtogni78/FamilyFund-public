<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Repositories\AssetRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class AssetController extends AppBaseController
{
    /** @var  AssetRepository */
    protected $assetRepository;

    public function __construct(AssetRepository $assetRepo)
    {
        $this->assetRepository = $assetRepo;
    }

    /**
     * Display a listing of the Asset.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $assets = $this->assetRepository->all();

        return view('assets.index')
            ->with('assets', $assets);
    }

    /**
     * Show the form for creating a new Asset.
     *
     * @return Response
     */
    public function create()
    {
        return view('assets.create');
    }

    /**
     * Store a newly created Asset in storage.
     *
     * @param CreateAssetRequest $request
     *
     * @return Response
     */
    public function store(CreateAssetRequest $request)
    {
        $input = $request->all();

        $asset = $this->assetRepository->create($input);

        Flash::success('Asset saved successfully.');

        return redirect(route('assets.index'));
    }

    /**
     * Display the specified Asset.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $asset = $this->assetRepository->find($id);

        if (empty($asset)) {
            Flash::error('Asset not found');

            return redirect(route('assets.index'));
        }

        return view('assets.show')->with('asset', $asset);
    }

    /**
     * Show the form for editing the specified Asset.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $asset = $this->assetRepository->find($id);

        if (empty($asset)) {
            Flash::error('Asset not found');

            return redirect(route('assets.index'));
        }

        return view('assets.edit')->with('asset', $asset);
    }

    /**
     * Update the specified Asset in storage.
     *
     * @param int $id
     * @param UpdateAssetRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAssetRequest $request)
    {
        $asset = $this->assetRepository->find($id);

        if (empty($asset)) {
            Flash::error('Asset not found');

            return redirect(route('assets.index'));
        }

        $asset = $this->assetRepository->update($request->all(), $id);

        Flash::success('Asset updated successfully.');

        return redirect(route('assets.index'));
    }

    /**
     * Remove the specified Asset from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $asset = $this->assetRepository->find($id);

        if (empty($asset)) {
            Flash::error('Asset not found');

            return redirect(route('assets.index'));
        }

        $this->assetRepository->delete($id);

        Flash::success('Asset deleted successfully.');

        return redirect(route('assets.index'));
    }
}
