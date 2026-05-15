<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CancelCreditLineRequest;
use App\Http\Requests\CreateAccountCreditLineRequest;
use App\Http\Requests\ReadjustCreditLineRequest;
use App\Http\Requests\RepayCreditLineRequest;
use App\Http\Requests\UpdateAccountCreditLineRequest;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\AccountExt;
use App\Models\UserExt;
use App\Services\CreditLine\Adjust\AdjustmentHistoryBuilder;
use App\Services\CreditLine\Adjust\ReadjustService;
use App\Services\CreditLine\Adjust\ScheduleSnapshotBuilder;
use App\Services\CreditLine\Cancel\CancelService;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Exceptions\CancelNotAllowedException;
use App\Services\CreditLine\Exceptions\NoChangeException;
use App\Services\CreditLine\Exceptions\OverBorrowException;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Reporting\LoansSummaryBuilder;
use App\Services\CreditLine\Reporting\TrajectoryBuilder;
use App\Services\CreditLine\Simulation\PaymentSimulator;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AccountCreditLineControllerExt extends AppBaseController
{
    public function __construct(
        private readonly DrawService $drawService,
        private readonly RepayService $repayService,
        private readonly ReadjustService $readjustService,
        private readonly CancelService $cancelService,
        private readonly AdjustmentHistoryBuilder $historyBuilder,
    ) {}

    /**
     * Admin gate for read-only credit-line endpoints.
     *
     * Wave-2 review (2026-05-14) flagged that index/show/create/edit had no
     * is_admin() check — only `simulator()` did. A non-admin authenticated
     * user could GET other accounts' credit-line data. Mirror the simulator
     * gate pattern here as a shared helper.
     */
    private function ensureAdmin(): void
    {
        if (!auth()->user()?->is_admin()) {
            abort(403);
        }
    }

    public function index($accountId)
    {
        $this->ensureAdmin();
        $account = AccountExt::findOrFail($accountId);
        $this->authorize('viewAny', [AccountCreditLineExt::class, $account]);
        $lines = AccountCreditLine::where('account_id', $account->id)
            ->orderByDesc('id')
            ->get();

        return view('account_credit_lines.index')
            ->with('account', $account)
            ->with('lines', $lines);
    }

    public function create($accountId)
    {
        $this->ensureAdmin();
        $account = AccountExt::findOrFail($accountId);
        $this->authorize('create', [AccountCreditLineExt::class, $account]);

        return view('account_credit_lines.create')
            ->with('account', $account);
    }

    public function store(CreateAccountCreditLineRequest $request)
    {
        $data = $request->validated();
        $account = AccountExt::findOrFail($data['account_id']);
        $this->authorize('create', [AccountCreditLineExt::class, $account]);

        try {
            $line = $this->drawService->open(
                $account,
                (float) $data['principal_shares'],
                (int) $data['term_months'],
                $data['payment_frequency'],
                $data['descr'] ?? null,
                null
            );
        } catch (OverBorrowException $e) {
            Flash::error($e->getMessage());
            return redirect()->back()->withInput();
        }

        Flash::success('Credit line #' . $line->id . ' opened.');

        return redirect(route('credit_lines.show', ['line' => $line->id]));
    }

    public function show(
        $id,
        TrajectoryBuilder $trajectoryBuilder,
        LoansSummaryBuilder $loansSummaryBuilder,
        ScheduleSnapshotBuilder $snapshotBuilder
    ) {
        $this->ensureAdmin();
        $line = AccountCreditLine::findOrFail($id);
        $this->authorize('view', $line);
        $account = $line->account()->first();
        $history = $this->historyBuilder->build($line);
        $schedule = $line->payments()->orderBy('due_date')->get();
        $trajectory = $trajectoryBuilder->build($line);
        $loansSummary = $account ? $loansSummaryBuilder->forAccount($account) : [];

        // Build per-adjustment schedule snapshots so the timeline can render
        // an inline "View schedule at this point" modal for each adjustment
        // (Phase 4, deferred from Phase 3).
        $scheduleSnapshots = [];
        foreach ($history as $entry) {
            if (($entry['kind'] ?? null) !== 'adjustment') {
                continue;
            }
            $adjId = $entry['data']['id'] ?? null;
            $adjAt = $entry['data']['adjusted_at'] ?? $entry['date'] ?? null;
            if ($adjId && $adjAt) {
                $scheduleSnapshots[$adjId] = $snapshotBuilder->snapshotAt($line, $adjAt);
            }
        }

        return view('account_credit_lines.show')
            ->with('line', $line)
            ->with('account', $account)
            ->with('schedule', $schedule)
            ->with('history', $history)
            ->with('trajectory', $trajectory)
            ->with('loansSummary', $loansSummary)
            ->with('scheduleSnapshots', $scheduleSnapshots);
    }

    public function edit($id)
    {
        $this->ensureAdmin();
        $line = AccountCreditLine::findOrFail($id);
        $this->authorize('update', $line);

        return view('account_credit_lines.edit')
            ->with('line', $line);
    }

    /**
     * UC-20: persist notification-settings edits.
     *
     * Only the 7 reminder / email columns are mutable here. Principal, term,
     * status, etc. are protected — use Readjust / Cancel for those.
     */
    public function update(UpdateAccountCreditLineRequest $request, $id)
    {
        $line = AccountCreditLine::findOrFail($id);
        $this->authorize('update', $line);

        $line->fill($request->validated())->save();

        Flash::success('Notification settings updated for credit line #' . $line->id . '.');

        return redirect(route('credit_lines.show', ['line' => $line->id]));
    }

    public function repay(RepayCreditLineRequest $request)
    {
        $data = $request->validated();
        $line = AccountCreditLine::findOrFail($data['account_credit_line_id']);
        $this->authorize('process', $line);
        $date = isset($data['date']) ? Carbon::parse($data['date']) : null;

        try {
            $tran = $this->repayService->repay($line, (float) $data['shares'], $date);
        } catch (InvalidArgumentException $e) {
            Flash::error($e->getMessage());
            return redirect(route('credit_lines.show', ['line' => $line->id]));
        }

        Flash::success('Repayment recorded (txn #' . $tran->id . ').');

        return redirect(route('credit_lines.show', ['line' => $line->id]));
    }

    public function readjust(ReadjustCreditLineRequest $request)
    {
        $data = $request->validated();
        $line = AccountCreditLine::findOrFail($data['account_credit_line_id']);
        $this->authorize('process', $line);

        /** @var UserExt|null $admin */
        $admin = auth()->user();
        if ($admin && !$admin instanceof UserExt) {
            $admin = UserExt::find($admin->id);
        }

        try {
            $adj = $this->readjustService->readjust(
                $line,
                isset($data['new_term_months']) ? (int) $data['new_term_months'] : null,
                $data['new_payment_frequency'] ?? null,
                $admin,
                $data['reason'] ?? null
            );
        } catch (NoChangeException $e) {
            Flash::error($e->getMessage());
            return redirect()->back()->withInput();
        }

        Flash::success('Credit line readjusted (adjustment #' . $adj->id . ').');

        return redirect(route('credit_lines.show', ['line' => $line->id]));
    }

    public function cancel(CancelCreditLineRequest $request)
    {
        $data = $request->validated();
        $line = AccountCreditLine::findOrFail($data['account_credit_line_id']);
        $this->authorize('delete', $line);

        try {
            $this->cancelService->cancel($line);
        } catch (CancelNotAllowedException $e) {
            Flash::error($e->getMessage());
            return redirect(route('credit_lines.show', ['line' => $line->id]));
        }

        Flash::success('Credit line cancelled.');

        return redirect(route('credit_lines.show', ['line' => $line->id]));
    }

    /**
     * Phase 9: payment simulator — read-only "what if" projection.
     *
     * Admin-gated. Renders the simulator view. If a positive
     * `monthly_payment_usd` is supplied via the query string, runs the
     * three-scenario simulation (conservative / expected / aggressive)
     * and passes the results to the view; otherwise renders the form
     * with no results.
     */
    public function simulator($id, Request $request, PaymentSimulator $simulator)
    {
        $this->ensureAdmin();

        $line = AccountCreditLine::findOrFail($id);
        $this->authorize('process', $line);
        $account = $line->account()->first();

        $currentShareValue = null;
        try {
            $currentShareValue = $account
                ? (float) $account->shareValueAsOf(Carbon::today()->toDateString())
                : null;
        } catch (\Throwable $e) {
            $currentShareValue = null;
        }

        $mode = $request->query('mode', 'payment');
        if (!in_array($mode, ['payment', 'time'], true)) {
            $mode = 'payment';
        }

        $monthlyPaymentUsd = $request->query('monthly_payment_usd');
        $targetMonths = $request->query('target_months');
        $results = [];
        $solvedPayments = [];
        $error = null;

        if ($mode === 'time') {
            if ($targetMonths !== null && $targetMonths !== '') {
                $targetMonths = (int) $targetMonths;
                if ($targetMonths < 1 || $targetMonths > 600) {
                    $error = 'target_months must be between 1 and 600';
                } else {
                    try {
                        $all = $simulator->solveForPaymentAllScenarios($line, $targetMonths);
                        foreach ($all as $k => $row) {
                            $results[$k] = $row['result'];
                            $solvedPayments[$k] = $row['payment'];
                        }
                    } catch (InvalidArgumentException $e) {
                        $error = $e->getMessage();
                    }
                }
            } else {
                $targetMonths = null;
            }
        } else {
            if ($monthlyPaymentUsd !== null && $monthlyPaymentUsd !== '') {
                $monthlyPaymentUsd = (float) $monthlyPaymentUsd;
                try {
                    $results = $simulator->simulateAllScenarios($line, $monthlyPaymentUsd);
                } catch (InvalidArgumentException $e) {
                    $error = $e->getMessage();
                }
            } else {
                $monthlyPaymentUsd = null;
            }
        }

        return view('account_credit_lines.simulator')
            ->with('line', $line)
            ->with('account', $account)
            ->with('currentShareValue', $currentShareValue)
            ->with('mode', $mode)
            ->with('monthlyPaymentUsd', is_numeric($monthlyPaymentUsd) ? (float) $monthlyPaymentUsd : null)
            ->with('targetMonths', is_numeric($targetMonths) ? (int) $targetMonths : null)
            ->with('results', $results)
            ->with('solvedPayments', $solvedPayments)
            ->with('simError', $error);
    }
}
