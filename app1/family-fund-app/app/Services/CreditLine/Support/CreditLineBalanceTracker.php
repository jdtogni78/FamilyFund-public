<?php

namespace App\Services\CreditLine\Support;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineBalance;
use App\Models\TransactionExt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Maintains the temporal balance history for AccountCreditLine.outstanding_shares.
 *
 * Whenever a wave-1 service mutates outstanding_shares (Draw, Repay, Reverse),
 * it calls recordChange() to:
 *   - Close the current active row (set end_dt = today).
 *   - Open a new active row with the new outstanding value.
 *
 * Idempotent: if the latest active row already records the same outstanding,
 * no-op. This makes it safe to call multiple times for the same mutation.
 *
 * Use FundReceivableCalculator / AccountCreditLineBalance queries to read the
 * outstanding as of any historical date.
 */
class CreditLineBalanceTracker
{
    /**
     * Record a change to a credit line's outstanding shares.
     *
     * @param  AccountCreditLine $line             The line being updated (after the change).
     * @param  float             $newOutstanding   The new outstanding-shares value.
     * @param  TransactionExt|null $causingTran    The BOR/REP/Reversal causing the change (optional).
     * @param  Carbon|null       $effectiveDate    The date the change takes effect (defaults to today).
     */
    public function recordChange(
        AccountCreditLine $line,
        float $newOutstanding,
        ?TransactionExt $causingTran = null,
        ?Carbon $effectiveDate = null
    ): void {
        $newOutstanding = round($newOutstanding, 4);
        $effectiveDate = $effectiveDate ?? Carbon::today();

        DB::transaction(function () use ($line, $newOutstanding, $causingTran, $effectiveDate) {
            $latest = AccountCreditLineBalance::where('account_credit_line_id', $line->id)
                ->where('end_dt', '9999-12-31')
                ->orderByDesc('start_dt')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($latest && (float) $latest->outstanding_shares === $newOutstanding) {
                // Idempotent: same outstanding already recorded — nothing to do.
                return;
            }

            if ($latest) {
                // Close the prior active row at the effective date.
                $latest->end_dt = $effectiveDate->toDateString();
                $latest->save();
            }

            AccountCreditLineBalance::create([
                'account_credit_line_id' => $line->id,
                'outstanding_shares'     => $newOutstanding,
                'start_dt'               => $effectiveDate->toDateString(),
                'end_dt'                 => '9999-12-31',
                'transaction_id'         => $causingTran?->id,
            ]);
        });
    }
}
