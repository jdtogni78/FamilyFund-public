<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAssetChangeLogRequest;
use App\Http\Requests\UpdateAssetChangeLogRequest;
use App\Repositories\AssetChangeLogRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class AssetChangeLogController extends AppBaseController
{
    /** @var  AssetChangeLogRepository */
    protected $assetChangeLogRepository;

    public function __construct(AssetChangeLogRepository $assetChangeLogRepo)
    {
        $this->assetChangeLogRepository = $assetChangeLogRepo;
    }

    /**
     * Display a listing of the AssetChangeLog.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $assetChangeLogs = $this->assetChangeLogRepository->all();

        return view('asset_change_logs.index')
            ->with('assetChangeLogs', $assetChangeLogs);
    }

    /**
     * Show the form for creating a new AssetChangeLog.
     *
     * @return Response
     */
    public function create()
    {
        return view('asset_change_logs.create');
    }

    /**
     * Store a newly created AssetChangeLog in storage.
     *
     * @param CreateAssetChangeLogRequest $request
     *
     * @return Response
     */
    public function store(CreateAssetChangeLogRequest $request)
    {
        $input = $request->all();

        $assetChangeLog = $this->assetChangeLogRepository->create($input);

        Flash::success('Asset Change Log saved successfully.');

        return redirect(route('assetChangeLogs.index'));
    }

    /**
     * Display the specified AssetChangeLog.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $assetChangeLog = $this->assetChangeLogRepository->find($id);

        if (empty($assetChangeLog)) {
            Flash::error('Asset Change Log not found');

            return redirect(route('assetChangeLogs.index'));
        }

        return view('asset_change_logs.show')->with('assetChangeLog', $assetChangeLog);
    }

    /**
     * Show the form for editing the specified AssetChangeLog.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $assetChangeLog = $this->assetChangeLogRepository->find($id);

        if (empty($assetChangeLog)) {
            Flash::error('Asset Change Log not found');

            return redirect(route('assetChangeLogs.index'));
        }

        return view('asset_change_logs.edit')->with('assetChangeLog', $assetChangeLog);
    }

    /**
     * Update the specified AssetChangeLog in storage.
     *
     * @param int $id
     * @param UpdateAssetChangeLogRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAssetChangeLogRequest $request)
    {
        $assetChangeLog = $this->assetChangeLogRepository->find($id);

        if (empty($assetChangeLog)) {
            Flash::error('Asset Change Log not found');

            return redirect(route('assetChangeLogs.index'));
        }

        $assetChangeLog = $this->assetChangeLogRepository->update($request->all(), $id);

        Flash::success('Asset Change Log updated successfully.');

        return redirect(route('assetChangeLogs.index'));
    }

    /**
     * Remove the specified AssetChangeLog from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $assetChangeLog = $this->assetChangeLogRepository->find($id);

        if (empty($assetChangeLog)) {
            Flash::error('Asset Change Log not found');

            return redirect(route('assetChangeLogs.index'));
        }

        $this->assetChangeLogRepository->delete($id);

        Flash::success('Asset Change Log deleted successfully.');

        return redirect(route('assetChangeLogs.index'));
    }
}
