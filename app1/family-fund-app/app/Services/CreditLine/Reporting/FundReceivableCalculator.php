<?php

namespace App\Services\CreditLine\Reporting;

use App\Models\Account;
use App\Models\AccountCreditLine;
use App\Models\FundExt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Computes the fund's outstanding credit-line receivable (UC-22).
 *
 * See docs/credit_lines/fund_cashflow.md for the receivable-as-asset
 * decision. The receivable is the sum of outstanding shares across active
 * credit lines on accounts in the fund, valued at the fund's share price.
 *
 * Phase 4 update: receivableShares now consults the temporal
 * account_credit_line_balances table so the receivable can be reconstructed
 * as of any historical date. Falls back to the live outstanding_shares column
 * when the balance table is unavailable (older databases) or for forward-only
 * lookups beyond the recorded history.
 */
class FundReceivableCalculator
{
    /**
     * Total outstanding shares the fund has lent to its accounts and not yet
     * received back, as of $asOf (defaults to today).
     */
    public function receivableShares(FundExt $fund, ?Carbon $asOf = null): float
    {
        $accountIds = Account::where('fund_id', $fund->id)->pluck('id');
        if ($accountIds->isEmpty()) {
            return 0.0;
        }

        $asOfDate = $asOf ? $asOf->toDateString() : Carbon::today()->toDateString();

        // Prefer the temporal balance table when available.
        if (Schema::hasTable('account_credit_line_balances')) {
            // Single round-trip: sum each line's contribution in the DB instead
            // of one covering-row query (plus an existence query) per line.
            //
            //   - If a balance interval covers $asOfDate
            //     (start_dt <= asOf < end_dt), use its outstanding_shares. The
            //     temporal intervals don't overlap — a same-day change closes a
            //     zero-length [d, d) interval, which can never cover any date —
            //     so at most one row matches per line and the join can't fan out.
            //   - Else, if the line has NO balance history at all AND is active
            //     AND $asOfDate is on/after origination, fall back to the live
            //     outstanding_shares column. Preserves lines created outside the
            //     tracker (legacy data, tests, admin SQL backfills).
            //   - Otherwise the line contributes 0.
            $haveHistory = DB::table('account_credit_line_balances')
                ->select('account_credit_line_id')
                ->groupBy('account_credit_line_id');

            $total = DB::table('account_credit_lines as acl')
                ->whereIn('acl.account_id', $accountIds)
                ->leftJoin('account_credit_line_balances as cov', function ($join) use ($asOfDate) {
                    $join->on('cov.account_credit_line_id', '=', 'acl.id')
                        ->where('cov.start_dt', '<=', $asOfDate)
                        ->where('cov.end_dt', '>', $asOfDate);
                })
                ->leftJoinSub($haveHistory, 'hist', 'hist.account_credit_line_id', '=', 'acl.id')
                ->selectRaw(
                    'coalesce(sum(case '
                    . 'when cov.id is not null then cov.outstanding_shares '
                    . "when hist.account_credit_line_id is null and acl.status = 'active' "
                    . 'and (acl.origination_date is null or acl.origination_date <= ?) '
                    . 'then acl.outstanding_shares '
                    . 'else 0 end), 0) as total',
                    [$asOfDate]
                )
                ->value('total');

            return (float) $total;
        }

        // Fallback: pre-Phase-4 schema. Treat receivable as point-in-time current.
        return (float) AccountCreditLine::whereIn('account_id', $accountIds)
            ->where('status', 'active')
            ->sum('outstanding_shares');
    }

    /**
     * Dollar value of the receivable at the given as-of date (or now).
     */
    public function receivableValue(FundExt $fund, ?Carbon $asOf = null): float
    {
        $shares = $this->receivableShares($fund, $asOf);
        if ($shares == 0.0) {
            return 0.0;
        }
        $sharePrice = (float) $fund->shareValueAsOf($asOf ? $asOf->toDateString() : now()->toDateString());
        return $shares * $sharePrice;
    }
}
