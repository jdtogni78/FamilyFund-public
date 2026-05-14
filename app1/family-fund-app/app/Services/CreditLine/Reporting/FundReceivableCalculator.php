<?php

namespace App\Services\CreditLine\Reporting;

use App\Models\Account;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineBalance;
use App\Models\FundExt;
use Carbon\Carbon;
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
            $lines = AccountCreditLine::whereIn('account_id', $accountIds)->get();
            if ($lines->isEmpty()) {
                return 0.0;
            }

            $total = 0.0;
            foreach ($lines as $line) {
                $row = AccountCreditLineBalance::where('account_credit_line_id', $line->id)
                    ->where('start_dt', '<=', $asOfDate)
                    ->where('end_dt', '>', $asOfDate)
                    ->orderByDesc('start_dt')
                    ->orderByDesc('id')
                    ->first();

                if ($row) {
                    $total += (float) $row->outstanding_shares;
                    continue;
                }

                // No covering row. Fall back to the live column when the line has
                // no balance history at all AND it is active AND $asOfDate is on
                // or after origination — this preserves correct behavior for lines
                // created outside the tracker (e.g., legacy data, tests, or admin
                // SQL backfills).
                $hasAny = AccountCreditLineBalance::where('account_credit_line_id', $line->id)->exists();
                if (!$hasAny
                    && $line->status === 'active'
                    && (!$line->origination_date || $asOfDate >= $line->origination_date->toDateString())
                ) {
                    $total += (float) $line->outstanding_shares;
                }
            }

            return $total;
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
