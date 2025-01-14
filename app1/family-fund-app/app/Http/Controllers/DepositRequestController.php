<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateDepositRequestRequest;
use App\Http\Requests\UpdateDepositRequestRequest;
use App\Repositories\DepositRequestRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class DepositRequestController extends AppBaseController
{
    /** @var DepositRequestRepository $depositRequestRepository*/
    private $depositRequestRepository;

    public function __construct(DepositRequestRepository $depositRequestRepo)
    {
        $this->depositRequestRepository = $depositRequestRepo;
    }

    /**
     * Display a listing of the DepositRequest.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $depositRequests = $this->depositRequestRepository->all();

        return view('deposit_requests.index')
            ->with('depositRequests', $depositRequests);
    }

    /**
     * Show the form for creating a new DepositRequest.
     *
     * @return Response
     */
    public function create()
    {
        return view('deposit_requests.create');
    }

    /**
     * Store a newly created DepositRequest in storage.
     *
     * @param CreateDepositRequestRequest $request
     *
     * @return Response
     */
    public function store(CreateDepositRequestRequest $request)
    {
        $input = $request->all();

        $depositRequest = $this->depositRequestRepository->create($input);

        Flash::success('Deposit Request saved successfully.');

        return redirect(route('depositRequests.index'));
    }

    /**
     * Display the specified DepositRequest.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $depositRequest = $this->depositRequestRepository->find($id);

        if (empty($depositRequest)) {
            Flash::error('Deposit Request not found');

            return redirect(route('depositRequests.index'));
        }

        return view('deposit_requests.show')->with('depositRequest', $depositRequest);
    }

    /**
     * Show the form for editing the specified DepositRequest.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $depositRequest = $this->depositRequestRepository->find($id);

        if (empty($depositRequest)) {
            Flash::error('Deposit Request not found');

            return redirect(route('depositRequests.index'));
        }

        return view('deposit_requests.edit')->with('depositRequest', $depositRequest);
    }

    /**
     * Update the specified DepositRequest in storage.
     *
     * @param int $id
     * @param UpdateDepositRequestRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateDepositRequestRequest $request)
    {
        $depositRequest = $this->depositRequestRepository->find($id);

        if (empty($depositRequest)) {
            Flash::error('Deposit Request not found');

            return redirect(route('depositRequests.index'));
        }

        $depositRequest = $this->depositRequestRepository->update($request->all(), $id);

        Flash::success('Deposit Request updated successfully.');

        return redirect(route('depositRequests.index'));
    }

    /**
     * Remove the specified DepositRequest from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $depositRequest = $this->depositRequestRepository->find($id);

        if (empty($depositRequest)) {
            Flash::error('Deposit Request not found');

            return redirect(route('depositRequests.index'));
        }

        $this->depositRequestRepository->delete($id);

        Flash::success('Deposit Request deleted successfully.');

        return redirect(route('depositRequests.index'));
    }
}
