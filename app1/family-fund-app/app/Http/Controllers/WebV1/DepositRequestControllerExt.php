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
use App\Http\Controllers\DepositRequestController;
use App\Models\AccountExt;
use App\Http\Controllers\Traits\AccountSelectorTrait;

class DepositRequestControllerExt extends DepositRequestController
{
    use AccountSelectorTrait;

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
        $api = $this->getAccountSelectorData();
        return parent::edit($id)->with('api', $api);
    }
}
