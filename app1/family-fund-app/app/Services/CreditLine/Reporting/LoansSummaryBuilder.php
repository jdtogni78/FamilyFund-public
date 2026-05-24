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
    /**
     * @param  Carbon|null $asOf  When provided, returns the summary as it was
     *         at that date — lines whose origination_date is in the future
     *         relative to $asOf are excluded, totals are recomputed from the
     *         transaction history at that date, and net outstanding comes
     *         from the BOR ledger row that was open at $asOf. (QA-2026-05-21
     *         #7, #16: the present-day version corrupts as-of views and the
     *         "Lifetime repaid" tile.)
     */
    public function forAccount(AccountExt $account, ?Carbon $asOf = null): array
    {
        $today    = Carbon::today();
        $asOfDate = $asOf ? $asOf->copy()->startOfDay() : $today;
        $asOfStr  = $asOfDate->toDateString();

        // Lines that existed at $asOf.
        $lines = AccountCreditLine::where('account_id', $account->id)
            ->whereDate('origination_date', '<=', $asOfStr)
            ->get();

        $totalDisbursed = (float) $lines->sum('principal_shares');

        // Lifetime repaid is the share of principal actually paid back across
        // lines: Σ (principal − outstanding_at_$asOf). Using SUM(transactions
        // WHERE type=REP) here would let overpayments and corrupted REP rows
        // inflate the figure (QA-2026-05-21 #7).
        $isLive = $asOfStr >= $today->toDateString();
        $totalRepaid = 0.0;
        foreach ($lines as $line) {
            $outstandingAt = $isLive
                ? (float) $line->outstanding_shares
                : $this->outstandingForLineAsOf($line, $asOfStr);
            $totalRepaid += max(0.0, (float) $line->principal_shares - $outstandingAt);
        }

        $activeLines = $lines->where('status', 'active');

        // Net outstanding: live mode trusts the snapshot column (which the
        // OutstandingCalculator keeps in sync with the BOR ledger); historical
        // mode reads the BOR aggregate row open at $asOf (same source of
        // truth as availableToBorrow() in OutstandingCalculator).
        $netOutstanding = $isLive
            ? (float) $activeLines->sum('outstanding_shares')
            : (float) $account->borrowedSharesAsOf($asOfStr);

        $activeLineCount = $activeLines->count();

        // Next due / behind plan are "going-forward from $asOf" projections.
        $next = CreditLinePayment::whereIn('account_credit_line_id', $activeLines->pluck('id'))
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->where('due_date', '>=', $asOfStr)
            ->orderBy('due_date')
            ->first();

        $nextDueDate = $next ? Carbon::parse($next->due_date)->format('Y-m-d') : null;
        $nextDueShares = $next ? (float) $next->shares_due : 0.0;

        $behindIds = CreditLinePayment::whereIn('account_credit_line_id', $activeLines->pluck('id'))
            ->whereIn('status', [
                CreditLinePayment::STATUS_SCHEDULED,
                CreditLinePayment::STATUS_LATE,
                CreditLinePayment::STATUS_PARTIAL,
            ])
            ->where('due_date', '<', $asOfStr)
            ->pluck('account_credit_line_id')
            ->unique();
        $behindCount = $behindIds->count();

        $outstandingValue = 0.0;
        try {
            $sharePrice = (float) $account->shareValueAsOf($asOfStr);
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

    /**
     * Replay BOR/REP transactions on a line up to $asOfStr to recover its
     * outstanding shares at that date. Mirrors the counted-statuses filter in
     * OutstandingCalculator::recomputeForLine() so historical and live reads
     * agree.
     */
    private function outstandingForLineAsOf(AccountCreditLine $line, string $asOfStr): float
    {
        $rows = TransactionExt::where('account_credit_line_id', $line->id)
            ->whereIn('type', [TransactionExt::TYPE_BORROW, TransactionExt::TYPE_REPAY])
            ->where('reversed', false)
            ->where(function ($q) {
                $q->whereNull('credit_line_match_status')
                  ->orWhereIn('credit_line_match_status', [
                      TransactionExt::MATCH_STATUS_AUTO_MATCHED,
                      TransactionExt::MATCH_STATUS_MANUAL,
                  ]);
            })
            ->whereDate('timestamp', '<=', $asOfStr)
            ->get();

        $outstanding = 0.0;
        foreach ($rows as $row) {
            $outstanding += ($row->type === TransactionExt::TYPE_BORROW)
                ? (float) $row->shares
                : -(float) $row->shares;
        }
        return round(max(0.0, $outstanding), 4);
    }
}
