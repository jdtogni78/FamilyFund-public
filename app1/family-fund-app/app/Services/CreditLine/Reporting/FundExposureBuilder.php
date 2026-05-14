<?php

namespace App\Services\CreditLine\Reporting;

use App\Models\Account;
use App\Models\AccountCreditLine;
use App\Models\CreditLinePayment;
use App\Models\FundExt;
use App\Models\TransactionExt;
use Carbon\Carbon;

/**
 * Fund-page exposure aggregates (UC-15, UC-17).
 *
 * Returns:
 *   [
 *     'outstanding_shares'   => float, // sum across active lines in this fund
 *     'outstanding_value'    => float, // outstanding_shares × current share price
 *     'cash_outflow_active'  => float, // dollars net out the door (principal - repaid value)
 *     'active_line_count'    => int,
 *     'behind_plan_count'    => int,
 *     'total_lines'          => int,   // all statuses
 *   ]
 */
class FundExposureBuilder
{
    public function __construct(
        private readonly FundReceivableCalculator $receivable,
    ) {}

    public function forFund(FundExt $fund): array
    {
        $accountIds = Account::where('fund_id', $fund->id)->pluck('id');

        $allLines = AccountCreditLine::whereIn('account_id', $accountIds)->get();
        $activeLines = $allLines->where('status', 'active');

        $outstandingShares = (float) $activeLines->sum('outstanding_shares');
        $outstandingValue = $this->receivable->receivableValue($fund);

        // Cash outflow currently outstanding for the fund: this is the dollar
        // value, at today's share price, of the still-owed receivable. (At draw
        // time cash leaves at a share price; today's price tracks the receivable's
        // present economic value — see fund_cashflow.md.)
        $cashOutflowActive = $outstandingValue;

        $today = Carbon::today();
        $behindIds = CreditLinePayment::whereIn('account_credit_line_id', $activeLines->pluck('id'))
            ->whereIn('status', [
                CreditLinePayment::STATUS_SCHEDULED,
                CreditLinePayment::STATUS_LATE,
                CreditLinePayment::STATUS_PARTIAL,
            ])
            ->where('due_date', '<', $today->toDateString())
            ->pluck('account_credit_line_id')
            ->unique();

        return [
            'outstanding_shares'  => round($outstandingShares, 4),
            'outstanding_value'   => round($outstandingValue, 2),
            'cash_outflow_active' => round($cashOutflowActive, 2),
            'active_line_count'   => $activeLines->count(),
            'behind_plan_count'   => $behindIds->count(),
            'total_lines'         => $allLines->count(),
        ];
    }
}
