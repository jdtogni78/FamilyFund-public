<?php

namespace App\Services\CreditLine\Cancel;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\TransactionExt;
use App\Services\CreditLine\Exceptions\CancelNotAllowedException;
use Illuminate\Support\Facades\DB;

/**
 * CancelService — cancels an unused or fully-repaid credit line.
 *
 * Allowed when:
 *   a) outstanding_shares == 0 (fully repaid, or never drawn), OR
 *   b) No BOR transactions have ever been linked to this line.
 *
 * Sets line.status = cancelled.
 * Does NOT modify the aggregate BOR balance (already 0 for cancellable lines).
 *
 * Phase 2 dependency: Controller must enforce admin-only auth before calling cancel().
 */
class CancelService
{
    /**
     * Cancel the credit line if eligible.
     *
     * @param  AccountCreditLine $line
     * @throws CancelNotAllowedException  If the line has outstanding principal.
     */
    public function cancel(AccountCreditLine $line): void
    {
        DB::transaction(function () use ($line) {
            // Lock the line to avoid concurrent draws racing with the cancel.
            DB::table('account_credit_lines')->where('id', $line->id)->lockForUpdate()->first();

            $hasNoBorTransactions = !TransactionExt::where('account_credit_line_id', $line->id)
                ->where('type', TransactionExt::TYPE_BORROW)
                ->exists();

            $outstanding = round((float) $line->outstanding_shares, 4);

            // Allow cancel if: no draws ever made, OR outstanding is zero.
            if (!$hasNoBorTransactions && $outstanding > 0) {
                throw new CancelNotAllowedException($outstanding);
            }

            $line->status = AccountCreditLineExt::STATUS_CANCELLED;
            $line->save();
        });
    }
}
