<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\CreateDepositRequestRequest;
use App\Http\Requests\UpdateDepositRequestRequest;
use App\Repositories\DepositRequestRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;
use App\Models\DepositRequest;
use App\Models\DepositRequestExt;
use App\Models\AccountExt;
use App\Http\Controllers\Traits\AccountSelectorTrait;

class DepositRequestControllerExt extends AppBaseController
{
    use AccountSelectorTrait;

    /** @var DepositRequestRepository $depositRequestRepository*/
    private $depositRequestRepository;

    public function __construct(DepositRequestRepository $depositRequestRepo)
    {
        $this->depositRequestRepository = $depositRequestRepo;
    }

    /**
     * Display a listing of DepositRequests with filtering.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $query = DepositRequest::with(['account.fund']);

        // Apply filters
        $filters = [];

        if ($request->filled('fund_id')) {
            $filters['fund_id'] = $request->fund_id;
            $query->whereHas('account', function($q) use ($request) {
                $q->where('fund_id', $request->fund_id);
            });
        }

        if ($request->filled('account_id')) {
            $filters['account_id'] = $request->account_id;
            $query->where('account_id', $request->account_id);
        }

        $depositRequests = $query->orderByDesc('id')->get();

        $api = array_merge(
            $this->getAccountSelectorData(),
            ['filters' => $filters]
        );

        return view('deposit_requests.index')
            ->with('depositRequests', $depositRequests)
            ->with('api', $api)
            ->with('filters', $filters);
    }

    public function create()
    {
        $api = $this->getAccountSelectorData();
        return view('deposit_requests.create')->with('api', $api);
    }

    public function edit($id)
    {
        $depositRequest = $this->depositRequestRepository->find($id);

        if (empty($depositRequest)) {
            Flash::error('Deposit Request not found');

            return redirect(route('depositRequests.index'));
        }

        $api = $this->getAccountSelectorData();
        return view('deposit_requests.edit')
            ->with('depositRequest', $depositRequest)
            ->with('api', $api);
    }

    // --- inlined from former base ---

    public function store(CreateDepositRequestRequest $request)
    {
        $input = $request->all();

        $depositRequest = $this->depositRequestRepository->create($input);

        Flash::success('Deposit Request saved successfully.');

        return redirect(route('depositRequests.index'));
    }

    public function show($id)
    {
        $depositRequest = $this->depositRequestRepository->find($id);

        if (empty($depositRequest)) {
            Flash::error('Deposit Request not found');

            return redirect(route('depositRequests.index'));
        }

        return view('deposit_requests.show')->with('depositRequest', $depositRequest);
    }

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
