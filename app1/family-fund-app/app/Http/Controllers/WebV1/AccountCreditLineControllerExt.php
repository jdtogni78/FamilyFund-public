<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CancelCreditLineRequest;
use App\Http\Requests\CreateAccountCreditLineRequest;
use App\Http\Requests\ReadjustCreditLineRequest;
use App\Http\Requests\RepayCreditLineRequest;
use App\Models\AccountCreditLine;
use App\Models\AccountExt;
use App\Models\UserExt;
use App\Services\CreditLine\Adjust\AdjustmentHistoryBuilder;
use App\Services\CreditLine\Adjust\ReadjustService;
use App\Services\CreditLine\Cancel\CancelService;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Reporting\LoansSummaryBuilder;
use App\Services\CreditLine\Reporting\TrajectoryBuilder;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;

class AccountCreditLineControllerExt extends AppBaseController
{
    public function __construct(
        private readonly DrawService $drawService,
        private readonly RepayService $repayService,
        private readonly ReadjustService $readjustService,
        private readonly CancelService $cancelService,
        private readonly AdjustmentHistoryBuilder $historyBuilder,
    ) {}

    public function index($accountId)
    {
        $account = AccountExt::findOrFail($accountId);
        $lines = AccountCreditLine::where('account_id', $account->id)
            ->orderByDesc('id')
            ->get();

        return view('account_credit_lines.index')
            ->with('account', $account)
            ->with('lines', $lines);
    }

    public function create($accountId)
    {
        $account = AccountExt::findOrFail($accountId);

        return view('account_credit_lines.create')
            ->with('account', $account);
    }

    public function store(CreateAccountCreditLineRequest $request)
    {
        $data = $request->validated();
        $account = AccountExt::findOrFail($data['account_id']);

        $line = $this->drawService->open(
            $account,
            (float) $data['principal_shares'],
            (int) $data['term_months'],
            $data['payment_frequency'],
            $data['descr'] ?? null,
            null
        );

        Flash::success('Credit line #' . $line->id . ' opened.');

        return redirect(route('credit_lines.show', ['line' => $line->id]));
    }

    public function show($id, TrajectoryBuilder $trajectoryBuilder, LoansSummaryBuilder $loansSummaryBuilder)
    {
        $line = AccountCreditLine::findOrFail($id);
        $account = $line->account()->first();
        $history = $this->historyBuilder->build($line);
        $schedule = $line->payments()->orderBy('due_date')->get();
        $trajectory = $trajectoryBuilder->build($line);
        $loansSummary = $account ? $loansSummaryBuilder->forAccount($account) : [];

        return view('account_credit_lines.show')
            ->with('line', $line)
            ->with('account', $account)
            ->with('schedule', $schedule)
            ->with('history', $history)
            ->with('trajectory', $trajectory)
            ->with('loansSummary', $loansSummary);
    }

    public function edit($id)
    {
        $line = AccountCreditLine::findOrFail($id);

        return view('account_credit_lines.edit')
            ->with('line', $line);
    }

    public function repay(RepayCreditLineRequest $request)
    {
        $data = $request->validated();
        $line = AccountCreditLine::findOrFail($data['account_credit_line_id']);
        $date = isset($data['date']) ? Carbon::parse($data['date']) : null;

        $tran = $this->repayService->repay($line, (float) $data['shares'], $date);

        Flash::success('Repayment recorded (txn #' . $tran->id . ').');

        return redirect(route('credit_lines.show', ['line' => $line->id]));
    }

    public function readjust(ReadjustCreditLineRequest $request)
    {
        $data = $request->validated();
        $line = AccountCreditLine::findOrFail($data['account_credit_line_id']);

        /** @var UserExt|null $admin */
        $admin = auth()->user();
        if ($admin && !$admin instanceof UserExt) {
            $admin = UserExt::find($admin->id);
        }

        $adj = $this->readjustService->readjust(
            $line,
            isset($data['new_term_months']) ? (int) $data['new_term_months'] : null,
            $data['new_payment_frequency'] ?? null,
            $admin,
            $data['reason'] ?? null
        );

        Flash::success('Credit line readjusted (adjustment #' . $adj->id . ').');

        return redirect(route('credit_lines.show', ['line' => $line->id]));
    }

    public function cancel(CancelCreditLineRequest $request)
    {
        $data = $request->validated();
        $line = AccountCreditLine::findOrFail($data['account_credit_line_id']);

        $this->cancelService->cancel($line);

        Flash::success('Credit line cancelled.');

        return redirect(route('credit_lines.show', ['line' => $line->id]));
    }
}
