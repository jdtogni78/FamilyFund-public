<?php

namespace App\Services\CreditLine\Reverse;

use App\Models\AccountCreditLineExt;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Models\TransactionReversal;
use App\Models\UserExt;
use App\Services\CreditLine\Reverse\Exceptions\AlreadyReversedException;
use App\Services\CreditLine\Reverse\Exceptions\NotReversibleTypeException;
use App\Services\CreditLine\Support\CreditLineBalanceTracker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * ReverseService — UC-45: Admin reverses an erroneous BOR or REP transaction.
 *
 * Design decisions:
 * ─────────────────
 * 1. The reversed Transaction row is NEVER deleted — it is flagged `reversed = true`.
 * 2. No forward corrective transaction is created by this service. Re-applying to a
 *    different line is a separate admin step (a fresh REP via wave 1a's RepayService).
 * 3. Reversing a BOR decreases outstanding_shares (BOR no longer contributes).
 *    If outstanding reaches 0 and the line was active, the line stays active —
 *    status does NOT auto-flip to paid_off on reversal. paid_off is a forward-state
 *    transition (achieved by repaying all shares). A reversed BOR means the draw
 *    never happened; the line is simply open with lower (or zero) outstanding.
 * 4. Admin-role enforcement is a Phase 2 responsibility. The controller layer will
 *    gate calls to this service behind auth middleware. This service accepts any
 *    UserExt and records it as the auditor — see Phase 2 dependencies below.
 * 5. CreditLinePayment rows whose status is `paid` OR `partial` linked to this REP
 *    are both re-opened (to `scheduled` or `late` per due_date). Both statuses are
 *    handled because RepayService can leave a row as `partial` when a partial payment
 *    was applied (see plan §5 rule 14 and wave-1a coordination note).
 *
 * Phase 2 dependencies:
 * ─────────────────────
 * - Admin-only auth: The controller that calls reverse() must enforce the admin role.
 *   UserExt has no is_admin() method in the codebase (confirmed by grep). Access control
 *   is handled at the controller/middleware layer, not here.
 * - Immutable TransactionReversal rows: Eloquent does not enforce immutability at the
 *   model level. A Phase 2 task should add a model observer or policy to block updates
 *   on TransactionReversal rows after creation.
 * - Aggregate BOR AccountBalance update: after reversing a BOR transaction, the aggregate
 *   BOR AccountBalance row (per §5 rule 8) should be updated. This is currently handled
 *   by OutstandingCalculator::updateAggregateBorBalance() in wave 1a. To avoid coupling
 *   wave 1e to wave 1a, this update is deferred to Phase 2 when the services are composed
 *   by a shared orchestrator. The per-line outstanding_shares IS updated correctly here.
 */
class ReverseService
{
    public function __construct(
        private ReversalOutstandingRecomputer $recomputer,
        private ?CreditLineBalanceTracker $balanceTracker = null,
    ) {
        $this->balanceTracker = $this->balanceTracker ?? new CreditLineBalanceTracker();
    }

    /**
     * Reverse a BOR or REP transaction.
     *
     * @param  TransactionExt $tran    The transaction to reverse.
     * @param  UserExt        $admin   The admin performing the reversal (audit only; role not enforced here — Phase 2).
     * @param  string         $reason  Required free-text reason.
     * @return TransactionReversal     The immutable audit row.
     *
     * @throws AlreadyReversedException    If $tran is already reversed.
     * @throws NotReversibleTypeException  If $tran->type is not BOR or REP.
     * @throws InvalidArgumentException    If $reason is empty.
     */
    public function reverse(TransactionExt $tran, UserExt $admin, string $reason): TransactionReversal
    {
        // Validate before entering the DB transaction.
        $this->validate($tran, $reason);

        return DB::transaction(function () use ($tran, $admin, $reason) {
            // Re-read with a row lock to prevent concurrent reversal races.
            $tran = TransactionExt::lockForUpdate()->find($tran->id);

            // Re-validate inside the lock (double-check pattern).
            if ($tran->reversed) {
                throw new AlreadyReversedException($tran->id);
            }

            // Step 1: capture the original credit line FK before any modifications.
            $originalCreditLineId = $tran->account_credit_line_id;

            // Step 2: mark the transaction as reversed.
            $tran->reversed = true;
            $tran->save();

            // Step 3: re-open any CreditLinePayment rows this REP had satisfied.
            // Only REP transactions can have satisfied schedule rows.
            if ($tran->type === TransactionExt::TYPE_REPAY) {
                $this->reopenPaymentRows($tran);
            }

            // Step 4: recompute the line's outstanding_shares (reversed=true means excluded from sum).
            $outstanding = null;
            $line = null;
            if ($originalCreditLineId) {
                $line = \App\Models\AccountCreditLine::find($originalCreditLineId);
                if ($line) {
                    $outstanding = $this->recomputer->recompute($line);

                    // Step 5: if line was paid_off and outstanding > 0 after recompute, reopen it.
                    if ($line->status === AccountCreditLineExt::STATUS_PAID_OFF && $outstanding > 0) {
                        $line->status = AccountCreditLineExt::STATUS_ACTIVE;
                        $line->save();
                    }
                    // Note: reversing a BOR that brought outstanding to 0 does NOT
                    // auto-set status=paid_off. paid_off is a forward-state-only transition.
                    // The line stays active (or whatever its current status is) — admin
                    // can close it explicitly via CancelService if desired.

                    // Record the post-reversal outstanding for historical receivable
                    // reconstruction.
                    $line->refresh();
                    $this->balanceTracker->recordChange(
                        $line,
                        (float) $line->outstanding_shares,
                        $tran,
                        Carbon::today()
                    );
                }
            }

            // Step 6: write the immutable audit row.
            $reversal = TransactionReversal::create([
                'transaction_id'                => $tran->id,
                'original_target_credit_line_id' => $originalCreditLineId,
                'reversed_at'                   => Carbon::now(),
                'reversed_by_user_id'           => $admin->id,
                'reason'                        => $reason,
            ]);

            return $reversal;
        });
    }

    /**
     * Validate preconditions before entering the DB transaction.
     *
     * @throws AlreadyReversedException
     * @throws NotReversibleTypeException
     * @throws InvalidArgumentException
     */
    private function validate(TransactionExt $tran, string $reason): void
    {
        if ($tran->reversed) {
            throw new AlreadyReversedException($tran->id);
        }

        $reversibleTypes = [TransactionExt::TYPE_BORROW, TransactionExt::TYPE_REPAY];
        if (!in_array($tran->type, $reversibleTypes, true)) {
            throw new NotReversibleTypeException($tran->id, $tran->type);
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required to reverse a transaction.');
        }
    }

    /**
     * Re-open any CreditLinePayment rows that were marked paid or partial by this REP.
     *
     * Per §5 rule 14:
     *  - `paid`    → reverted back to `scheduled` (or `late` if due_date is past).
     *  - `partial` → reverted back to `scheduled` (or `late` if due_date is past).
     *
     * The paid_transaction_id FK is cleared so the row is available for re-payment.
     */
    private function reopenPaymentRows(TransactionExt $repTran): void
    {
        $today = Carbon::today();

        $rows = CreditLinePayment::where('paid_transaction_id', $repTran->id)
            ->whereIn('status', [CreditLinePayment::STATUS_PAID, CreditLinePayment::STATUS_PARTIAL])
            ->get();

        foreach ($rows as $row) {
            $dueDate = $row->due_date instanceof Carbon
                ? $row->due_date
                : Carbon::parse($row->due_date);

            $row->status = $dueDate->lt($today)
                ? CreditLinePayment::STATUS_LATE
                : CreditLinePayment::STATUS_SCHEDULED;

            $row->paid_transaction_id = null;
            $row->save();
        }
    }
}
