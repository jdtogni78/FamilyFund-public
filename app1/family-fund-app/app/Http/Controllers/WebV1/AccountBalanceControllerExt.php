<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AccountBalanceController;
use App\Http\Controllers\Traits\AccountSelectorTrait;
use App\Models\AccountBalance;
use Illuminate\Http\Request;

class AccountBalanceControllerExt extends AccountBalanceController
{
    use AccountSelectorTrait;

    /**
     * Display a listing of AccountBalances with filtering.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $query = AccountBalance::with(['account.fund']);

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

        $accountBalances = $query->orderByDesc('id')->get();

        $api = array_merge(
            $this->getAccountSelectorData(),
            ['filters' => $filters]
        );

        return view('account_balances.index')
            ->with('accountBalances', $accountBalances)
            ->with('api', $api)
            ->with('filters', $filters);
    }
}
