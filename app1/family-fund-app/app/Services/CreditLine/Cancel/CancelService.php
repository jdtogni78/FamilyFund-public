<?php

namespace App\Services\CreditLine\Cancel;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\AccountExt;
use App\Models\TransactionExt;
use App\Services\CreditLine\Exceptions\CancelNotAllowedException;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CancelService — cancels an unused or fully-repaid credit line.
 *
 * Allowed when:
 *   a) outstanding_shares == 0 (fully repaid, or never drawn), OR
 *   b) No BOR transactions have ever been linked to this line, OR
 *   c) caller passes $force=true (write-off path; outstanding is zeroed and
 *      the BOR aggregate ledger is refreshed so the cancelled line's
 *      contribution stops being counted by borrowedSharesAsOf()).
 *
 * Sets line.status = cancelled and line.outstanding_shares = 0.
 *
 * Phase 2 dependency: Controller must enforce admin-only auth before calling cancel().
 */
class CancelService
{
    public function __construct(
        private ?OutstandingCalculator $calculator = null,
    ) {
        $this->calculator = $this->calculator ?? new OutstandingCalculator();
    }

    /**
     * Cancel the credit line if eligible.
     *
     * @param  AccountCreditLine $line
     * @param  bool              $force  When true, bypasses the outstanding>0 guard
     *         (write-off / data-cleanup path). The line's outstanding is
     *         zeroed and the account's BOR aggregate ledger is refreshed.
     * @throws CancelNotAllowedException  If $force=false and the line has outstanding principal.
     */
    public function cancel(AccountCreditLine $line, bool $force = false): void
    {
        DB::transaction(function () use ($line, $force) {
            // Lock the line to avoid concurrent draws racing with the cancel.
            DB::table('account_credit_lines')->where('id', $line->id)->lockForUpdate()->first();

            $hasBorTransactions = TransactionExt::where('account_credit_line_id', $line->id)
                ->where('type', TransactionExt::TYPE_BORROW)
                ->exists();

            $outstanding = round((float) $line->outstanding_shares, 4);

            // Allow cancel if: no draws ever made, OR outstanding is zero,
            // OR caller explicitly requested a force/write-off cancel.
            if (!$force && $hasBorTransactions && $outstanding > 0) {
                throw new CancelNotAllowedException($outstanding);
            }

            $line->status = AccountCreditLineExt::STATUS_CANCELLED;
            $line->outstanding_shares = 0;
            $line->save();

            // Refresh the BOR aggregate ledger so the cancelled line's
            // contribution stops being counted by borrowedSharesAsOf(). The
            // calculator sums ACTIVE outstandings, so it naturally excludes
            // this (now cancelled) line. No-op when the line never had a BOR.
            if ($hasBorTransactions) {
                $borTxn = TransactionExt::where('account_credit_line_id', $line->id)
                    ->where('type', TransactionExt::TYPE_BORROW)
                    ->latest('id')
                    ->first();

                /** @var AccountExt $account */
                $account = AccountExt::find($line->account_id);
                if ($account && $borTxn) {
                    $this->calculator->updateAggregateBorBalance(
                        $account,
                        Carbon::today()->toDateString(),
                        $borTxn->id
                    );
                }
            }
        });
    }
}
