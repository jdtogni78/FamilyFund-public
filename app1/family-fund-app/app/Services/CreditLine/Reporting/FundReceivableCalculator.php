<?php

namespace App\Services\CreditLine\Reporting;

use App\Models\Account;
use App\Models\AccountCreditLine;
use App\Models\FundExt;
use Carbon\Carbon;

/**
 * Computes the fund's outstanding credit-line receivable (UC-22).
 *
 * See docs/credit_lines/fund_cashflow.md for the receivable-as-asset
 * decision. The receivable is the sum of `outstanding_shares` across active
 * credit lines on accounts in the fund, valued at the fund's share price.
 */
class FundReceivableCalculator
{
    /**
     * Total outstanding shares the fund has lent to its accounts and not yet
     * received back. Active lines only.
     */
    public function receivableShares(FundExt $fund, ?Carbon $asOf = null): float
    {
        // The receivable is "share-denominated debt currently owed back".
        // It's status-driven; we don't have a per-line balance history table
        // and AccountCreditLine.outstanding_shares is point-in-time. For now,
        // ignore $asOf (treat receivable as current). Documented as a Phase 3
        // limitation; a future migration can introduce account_credit_line_balances
        // to support historical reconstruction.
        $accountIds = Account::where('fund_id', $fund->id)->pluck('id');
        if ($accountIds->isEmpty()) {
            return 0.0;
        }

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
