<?php

namespace App\Services\CreditLine\Adjust;

use App\Models\AccountCreditLine;
use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Returns the schedule as it existed at a given point in time (UC-41).
 *
 * "Schedule as of $asOf" = all CreditLinePayment rows for the line
 * whose created_at <= $asOf (regardless of status). This captures both
 * the original schedule rows and any rows created by a readjustment up to
 * that point, faithfully reproducing "what the system had scheduled at that
 * moment in history".
 *
 * The caller can further filter by status (e.g., only 'scheduled' rows)
 * for display purposes; this builder returns the full unfiltered set so
 * the view layer has complete information.
 */
class ScheduleSnapshotBuilder
{
    /**
     * Return all CreditLinePayment rows created on or before $asOf.
     *
     * @param  AccountCreditLine $line
     * @param  Carbon            $asOf
     * @return Collection<CreditLinePayment>
     */
    public function snapshotAt(AccountCreditLine $line, Carbon $asOf): Collection
    {
        return CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('created_at', '<=', $asOf->toDateTimeString())
            ->orderBy('due_date')
            ->get();
    }
}
