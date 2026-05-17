<?php

namespace App\Services\CreditLine\Support;

use App\Models\AccountCreditLine;
use App\Models\CreditLinePayment;
use Carbon\Carbon;

/**
 * Flags overdue schedule rows as late.
 *
 * A row is "late" when it is still SCHEDULED and its due_date is strictly
 * before the as-of date. Only SCHEDULED rows are touched — PARTIAL rows are
 * left intact so their partial-payment linkage (paid_transaction_id) is not
 * lost, and PAID/CANCELLED rows are terminal.
 *
 * Used in two places:
 *  - DrawService::open() right after the schedule is built, so a backdated
 *    draw immediately shows its past-due rows as late (per product decision).
 *  - The `credit-lines:detect-late` artisan command, for ongoing sweeps.
 */
class LateDetector
{
    /**
     * Flag overdue SCHEDULED rows for a single line.
     *
     * @param  Carbon|null $asOf Defaults to today.
     * @return int Number of rows transitioned to late.
     */
    public function detectForLine(AccountCreditLine $line, ?Carbon $asOf = null): int
    {
        $asOf = $asOf ?? Carbon::today();

        return CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->whereDate('due_date', '<', $asOf->toDateString())
            ->update(['status' => CreditLinePayment::STATUS_LATE]);
    }

    /**
     * Flag overdue SCHEDULED rows across every active line.
     *
     * @param  Carbon|null $asOf Defaults to today.
     * @return int Total number of rows transitioned to late.
     */
    public function detectAll(?Carbon $asOf = null): int
    {
        $asOf = $asOf ?? Carbon::today();

        return CreditLinePayment::where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->whereDate('due_date', '<', $asOf->toDateString())
            ->update(['status' => CreditLinePayment::STATUS_LATE]);
    }
}
