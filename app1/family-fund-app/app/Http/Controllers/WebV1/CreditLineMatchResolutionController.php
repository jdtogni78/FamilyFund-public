<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\ResolveCreditLineMatchRequest;
use App\Models\AccountCreditLine;
use App\Models\TransactionExt;
use App\Services\CreditLine\Matching\MatchResolutionService;
use Flash;

class CreditLineMatchResolutionController extends AppBaseController
{
    public function __construct(
        private readonly MatchResolutionService $resolutionService,
    ) {}

    public function index()
    {
        $user = auth()->user();
        $accountIds = $user ? $user->getOwnAccountIds() : [];

        // Admins see everything flagged; non-admins (shouldn't reach here, but
        // be defensive) only see their own.
        $query = TransactionExt::whereIn('credit_line_match_status', [
            TransactionExt::MATCH_STATUS_AMBIGUOUS,
            TransactionExt::MATCH_STATUS_UNMATCHED,
        ]);

        if (!($user && $user->is_admin())) {
            $query->whereIn('account_id', $accountIds);
        }

        $flagged = $query->orderByDesc('timestamp')->get();

        return view('credit_lines.resolve_index')
            ->with('flagged', $flagged);
    }

    public function resolve(ResolveCreditLineMatchRequest $request)
    {
        $data = $request->validated();
        $tran = TransactionExt::findOrFail($data['transaction_id']);
        $line = AccountCreditLine::findOrFail($data['account_credit_line_id']);

        $this->resolutionService->resolve($tran, $line, auth()->user());

        Flash::success('Transaction #' . $tran->id . ' resolved to credit line #' . $line->id . '.');

        return redirect(route('credit_lines.resolve_index'));
    }
}
