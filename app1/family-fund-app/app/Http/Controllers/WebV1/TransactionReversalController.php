<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\ReverseTransactionRequest;
use App\Models\TransactionExt;
use App\Models\UserExt;
use App\Services\CreditLine\Reverse\Exceptions\AlreadyReversedException;
use App\Services\CreditLine\Reverse\ReverseService;
use Flash;

class TransactionReversalController extends AppBaseController
{
    public function __construct(
        private readonly ReverseService $reverseService,
    ) {}

    public function store(ReverseTransactionRequest $request)
    {
        $data = $request->validated();
        $tran = TransactionExt::findOrFail($data['transaction_id']);

        $admin = auth()->user();
        if ($admin && !$admin instanceof UserExt) {
            $admin = UserExt::find($admin->id);
        }

        try {
            $reversal = $this->reverseService->reverse($tran, $admin, $data['reason']);
        } catch (AlreadyReversedException $e) {
            Flash::error($e->getMessage());
            return redirect()->back();
        }

        Flash::success('Transaction #' . $tran->id . ' reversed (reversal #' . $reversal->id . ').');

        return redirect()->back();
    }
}
