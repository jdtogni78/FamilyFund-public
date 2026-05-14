<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\AdminCreateTransactionRequest;
use App\Models\AccountCreditLine;
use App\Models\AccountExt;
use App\Models\TransactionExt;
use Carbon\Carbon;
use Flash;

/**
 * UC-46: admin-only transaction-create UI that allows an arbitrary
 * (including backdated) timestamp.
 *
 * Unlike the normal transaction CRUD this controller:
 *   - persists the row directly (no `processPending()` business-logic path)
 *     so admins can record historical states verbatim without triggering
 *     fund-cash, balance-overlap and matching side-effects.
 *   - lets the `TransactionObserver` fire after save() so the detection
 *     pipeline (CreditLineClassifier / ContributionClassifier) still runs,
 *     consistent with every other transaction write path.
 */
class AdminTransactionController extends AppBaseController
{
    public function create()
    {
        $accounts    = AccountExt::orderBy('nickname')->get(['id', 'nickname']);
        $creditLines = AccountCreditLine::orderByDesc('id')
            ->get(['id', 'account_id', 'descr', 'status']);

        return view('admin.transactions.create')
            ->with('accounts', $accounts)
            ->with('creditLines', $creditLines)
            ->with('typeMap', TransactionExt::$typeMap)
            ->with('statusMap', TransactionExt::$statusMap);
    }

    public function store(AdminCreateTransactionRequest $request)
    {
        $data = $request->validated();

        $tran = new TransactionExt();
        $tran->account_id              = (int) $data['account_id'];
        $tran->account_credit_line_id  = isset($data['account_credit_line_id'])
            ? (int) $data['account_credit_line_id']
            : null;
        $tran->type      = $data['type'];
        $tran->status    = $data['status'];
        $tran->value     = (float) $data['value'];
        $tran->shares    = isset($data['shares']) ? (float) $data['shares'] : null;
        $tran->timestamp = Carbon::parse($data['timestamp']);
        $tran->descr     = $data['descr'] ?? null;
        $tran->save();

        Flash::success('Transaction #' . $tran->id . ' created with timestamp ' . $tran->timestamp->toDateTimeString() . '.');

        return redirect(route('admin.transactions.create'));
    }
}
