<?php

namespace App\Services\CreditLine\Reporting;

use App\Models\AccountCreditLine;
use App\Models\AccountExt;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use Carbon\Carbon;

/**
 * Loans summary card used on the account page and the quarterly report (UC-49).
 *
 * Returns a single aggregate snapshot across all credit lines on an account:
 *
 *   [
 *     'total_disbursed_shares'     => float, // lifetime principal across all lines
 *     'total_repaid_shares'        => float, // lifetime cleared REP shares (not reversed)
 *     'net_outstanding_shares'     => float, // sum of outstanding_shares on active lines
 *     'next_due_date'              => 'Y-m-d' | null,
 *     'next_due_shares'            => float, // shares due on that nearest payment
 *     'active_line_count'          => int,
 *     'behind_plan_count'          => int,   // active lines with any past-due scheduled rows
 *     'outstanding_value'          => float, // shares × current shareValue, when share price available
 *   ]
 */
class LoansSummaryBuilder
{
    public function forAccount(AccountExt $account): array
    {
        $lines = AccountCreditLine::where('account_id', $account->id)->get();

        $totalDisbursed = (float) $lines->sum('principal_shares');

        $totalRepaid = (float) TransactionExt::where('account_id', $account->id)
            ->where('type', TransactionExt::TYPE_REPAY)
            ->where('status', TransactionExt::STATUS_CLEARED)
            ->where('reversed', false)
            ->sum('shares');

        $activeLines = $lines->where('status', 'active');
        $netOutstanding = (float) $activeLines->sum('outstanding_shares');
        $activeLineCount = $activeLines->count();

        $today = Carbon::today();

        // Next due across all active lines.
        $next = CreditLinePayment::whereIn('account_credit_line_id', $activeLines->pluck('id'))
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->where('due_date', '>=', $today->toDateString())
            ->orderBy('due_date')
            ->first();

        $nextDueDate = $next ? Carbon::parse($next->due_date)->format('Y-m-d') : null;
        $nextDueShares = $next ? (float) $next->shares_due : 0.0;

        // Behind-plan = any active line with a scheduled row whose due_date is past.
        $behindIds = CreditLinePayment::whereIn('account_credit_line_id', $activeLines->pluck('id'))
            ->whereIn('status', [
                CreditLinePayment::STATUS_SCHEDULED,
                CreditLinePayment::STATUS_LATE,
                CreditLinePayment::STATUS_PARTIAL,
            ])
            ->where('due_date', '<', $today->toDateString())
            ->pluck('account_credit_line_id')
            ->unique();
        $behindCount = $behindIds->count();

        $outstandingValue = 0.0;
        try {
            $sharePrice = (float) $account->shareValueAsOf($today->toDateString());
            $outstandingValue = $netOutstanding * $sharePrice;
        } catch (\Throwable $e) {
            // Defensive: keep 0 if fund/share price not available.
        }

        return [
            'total_disbursed_shares' => round($totalDisbursed, 4),
            'total_repaid_shares'    => round($totalRepaid, 4),
            'net_outstanding_shares' => round($netOutstanding, 4),
            'next_due_date'          => $nextDueDate,
            'next_due_shares'        => round($nextDueShares, 4),
            'active_line_count'      => $activeLineCount,
            'behind_plan_count'      => $behindCount,
            'outstanding_value'      => round($outstandingValue, 2),
        ];
    }
}
