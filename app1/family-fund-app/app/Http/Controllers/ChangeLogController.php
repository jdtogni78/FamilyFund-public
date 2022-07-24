<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateChangeLogRequest;
use App\Http\Requests\UpdateChangeLogRequest;
use App\Repositories\ChangeLogRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class ChangeLogController extends AppBaseController
{
    /** @var ChangeLogRepository $changeLogRepository*/
    protected $changeLogRepository;

    public function __construct(ChangeLogRepository $changeLogRepo)
    {
        $this->changeLogRepository = $changeLogRepo;
    }

    /**
     * Display a listing of the ChangeLog.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $changeLogs = $this->changeLogRepository->all();

        return view('change_logs.index')
            ->with('changeLogs', $changeLogs);
    }

    /**
     * Show the form for creating a new ChangeLog.
     *
     * @return Response
     */
    public function create()
    {
        return view('change_logs.create');
    }

    /**
     * Store a newly created ChangeLog in storage.
     *
     * @param CreateChangeLogRequest $request
     *
     * @return Response
     */
    public function store(CreateChangeLogRequest $request)
    {
        $input = $request->all();

        $changeLog = $this->changeLogRepository->create($input);

        Flash::success('Change Log saved successfully.');

        return redirect(route('changeLogs.index'));
    }

    /**
     * Display the specified ChangeLog.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $changeLog = $this->changeLogRepository->find($id);

        if (empty($changeLog)) {
            Flash::error('Change Log not found');

            return redirect(route('changeLogs.index'));
        }

        return view('change_logs.show')->with('changeLog', $changeLog);
    }

    /**
     * Show the form for editing the specified ChangeLog.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $changeLog = $this->changeLogRepository->find($id);

        if (empty($changeLog)) {
            Flash::error('Change Log not found');

            return redirect(route('changeLogs.index'));
        }

        return view('change_logs.edit')->with('changeLog', $changeLog);
    }

    /**
     * Update the specified ChangeLog in storage.
     *
     * @param int $id
     * @param UpdateChangeLogRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateChangeLogRequest $request)
    {
        $changeLog = $this->changeLogRepository->find($id);

        if (empty($changeLog)) {
            Flash::error('Change Log not found');

            return redirect(route('changeLogs.index'));
        }

        $changeLog = $this->changeLogRepository->update($request->all(), $id);

        Flash::success('Change Log updated successfully.');

        return redirect(route('changeLogs.index'));
    }

    /**
     * Remove the specified ChangeLog from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $changeLog = $this->changeLogRepository->find($id);

        if (empty($changeLog)) {
            Flash::error('Change Log not found');

            return redirect(route('changeLogs.index'));
        }

        $this->changeLogRepository->delete($id);

        Flash::success('Change Log deleted successfully.');

        return redirect(route('changeLogs.index'));
    }
}
