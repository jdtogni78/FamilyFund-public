<?php

namespace App\Services\CreditLine\Repay;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\AccountExt;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Services\CreditLine\Matching\Contracts\ScheduleAdvancer;
use App\Services\CreditLine\Support\CreditLineBalanceTracker;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * RepayService — records a repayment against an explicit credit line.
 *
 * Responsibilities:
 * - Create a REP Transaction (type=REP, status=C, credit_line_match_status=NULL since target is explicit).
 * - Apply shares to schedule rows in due-date order:
 *     · Exact/full payment → row.status = paid; paid_transaction_id = repayTran.id.
 *     · Partial payment    → row.status = partial; remaining applied to next rows.
 *     · Extra payment      → cascade to subsequent scheduled rows; if all covered → line.status = paid_off.
 * - Recompute line.outstanding_shares via OutstandingCalculator.
 * - Update aggregate BOR AccountBalance row.
 *
 * Multi-line matcher routing is wave 1b. This service always receives an explicit line.
 *
 * Phase 2 dependency: Controller must enforce admin-only auth before calling repay().
 */
class RepayService implements ScheduleAdvancer
{
    public function __construct(
        private OutstandingCalculator $calculator,
        private ?CreditLineBalanceTracker $balanceTracker = null,
    ) {
        $this->balanceTracker = $this->balanceTracker ?? new CreditLineBalanceTracker();
    }

    /**
     * Record a repayment against the given line.
     *
     * @param  AccountCreditLine $line   The explicit target line.
     * @param  float             $shares Number of shares being repaid.
     * @param  Carbon|null       $date   Effective date (defaults to today).
     * @return TransactionExt           The created REP transaction.
     */
    public function repay(AccountCreditLine $line, float $shares, ?Carbon $date = null): TransactionExt
    {
        // Guard: shares must be positive.
        if ($shares <= 0) {
            throw new InvalidArgumentException(
                sprintf('Repayment shares must be positive; got %.4f.', $shares)
            );
        }

        // Guard: can only repay against active lines.
        if ($line->status !== AccountCreditLineExt::STATUS_ACTIVE) {
            throw new InvalidArgumentException(
                sprintf('Cannot repay a credit line with status "%s". Only active lines accept repayments.', $line->status)
            );
        }

        $date = $date ?? Carbon::today();

        return DB::transaction(function () use ($line, $shares, $date) {
            // Lock the line row to prevent concurrent updates.
            DB::table('account_credit_lines')->where('id', $line->id)->lockForUpdate()->first();

            $repTransaction = $this->createRepTransaction(
                $line,
                round($shares, 4),
                $date,
                'Credit line repayment'
            );

            // Apply shares to the schedule in due-date order.
            $this->applyToScheduleRows($line, $repTransaction, round($shares, 4));

            $this->finalize($line, $repTransaction, $date);

            return $repTransaction;
        });
    }

    /**
     * Register a payment against a SPECIFIC schedule row (per-row manual
     * registration — used to reconcile externally-settled installments).
     *
     * The targeted row is satisfied first. If `$shares` exceeds the row's
     * remaining balance, the overflow cascades to subsequent open rows in
     * due-date order (same rule as a normal repayment) so an over-payment is
     * never lost. A short payment marks the row `partial`.
     *
     * @param  CreditLinePayment $row    The schedule row being settled.
     * @param  float             $shares Shares paid (admin-editable; defaults to row's shares_due in the UI).
     * @param  Carbon|null       $date   Real settlement date (defaults to today).
     * @return TransactionExt            The created REP transaction.
     */
    public function repayRow(CreditLinePayment $row, float $shares, ?Carbon $date = null): TransactionExt
    {
        if ($shares <= 0) {
            throw new InvalidArgumentException(
                sprintf('Payment shares must be positive; got %.4f.', $shares)
            );
        }

        /** @var AccountCreditLine $line */
        $line = $row->creditLine()->first();
        if (!$line) {
            throw new InvalidArgumentException('Schedule row is not attached to a credit line.');
        }

        if ($line->status !== AccountCreditLineExt::STATUS_ACTIVE) {
            throw new InvalidArgumentException(
                sprintf('Cannot register a payment on a credit line with status "%s". Only active lines accept payments.', $line->status)
            );
        }

        $openStatuses = [
            CreditLinePayment::STATUS_SCHEDULED,
            CreditLinePayment::STATUS_PARTIAL,
            CreditLinePayment::STATUS_LATE,
        ];
        if (!in_array($row->status, $openStatuses, true)) {
            throw new InvalidArgumentException(
                sprintf('Schedule row #%d is "%s" and cannot accept a payment.', $row->id, $row->status)
            );
        }

        $date   = $date ?? Carbon::today();
        $shares = round($shares, 4);

        return DB::transaction(function () use ($line, $row, $shares, $date) {
            DB::table('account_credit_lines')->where('id', $line->id)->lockForUpdate()->first();

            $repTransaction = $this->createRepTransaction(
                $line,
                $shares,
                $date,
                sprintf('Credit line payment (schedule row #%d, due %s)', $row->id, $row->due_date)
            );

            // Satisfy the targeted row first; cascade any overflow.
            $row->refresh();
            $alreadyPaid  = $this->sharesAlreadyPaidOnRow($row);
            $rowRemaining = round((float) $row->shares_due - $alreadyPaid, 4);
            $remaining    = $shares;

            if ($remaining >= $rowRemaining) {
                $remaining -= $rowRemaining;
                $row->status              = CreditLinePayment::STATUS_PAID;
                $row->paid_transaction_id = $repTransaction->id;
                $row->save();

                // Overflow cascades to the remaining open rows oldest-first.
                if ($remaining > 0) {
                    $this->applyToScheduleRows($line, $repTransaction, round($remaining, 4));
                }
            } else {
                $row->status              = CreditLinePayment::STATUS_PARTIAL;
                $row->paid_transaction_id = $repTransaction->id;
                $row->save();
            }

            $this->finalize($line, $repTransaction, $date);

            return $repTransaction;
        });
    }

    /**
     * Create the REP transaction for an explicit-target repayment.
     *
     * credit_line_match_status is NULL — the target line is explicit, so the
     * matcher is not involved.
     */
    private function createRepTransaction(
        AccountCreditLine $line,
        float $shares,
        Carbon $date,
        string $descr
    ): TransactionExt {
        return TransactionExt::create([
            'account_id'               => $line->account_id,
            'account_credit_line_id'   => $line->id,
            'type'                     => TransactionExt::TYPE_REPAY,
            'status'                   => TransactionExt::STATUS_CLEARED,
            'value'                    => 0,  // cash-leg deferred to Phase 5
            'shares'                   => round($shares, 4),
            'timestamp'                => $date->toDateTimeString(),
            'credit_line_match_status' => null,
            'reversed'                 => false,
            'descr'                    => $descr,
        ]);
    }

    /**
     * Shared post-application tail: recompute outstanding, mark paid-off,
     * refresh the aggregate BOR balance, and record the balance change.
     */
    private function finalize(AccountCreditLine $line, TransactionExt $repTransaction, Carbon $date): void
    {
        $outstanding = $this->calculator->recomputeForLine($line);

        // Mark as paid off when fully repaid (UC-13).
        if ($outstanding <= 0) {
            $line->status = AccountCreditLineExt::STATUS_PAID_OFF;
            $line->outstanding_shares = 0;
            $line->save();
        }

        /** @var AccountExt $account */
        $account = $line->account()->first();
        $this->calculator->updateAggregateBorBalance($account, $date->toDateString(), $repTransaction->id);

        // Record the new outstanding for historical receivable reconstruction.
        $line->refresh();
        $this->balanceTracker->recordChange(
            $line,
            (float) $line->outstanding_shares,
            $repTransaction,
            $date
        );
    }

    /**
     * Public entry-point for advancing the schedule of an already-saved REP transaction.
     *
     * Used by:
     *  - MatchResolutionService (after admin resolves an ambiguous match)
     *  - TransactionObserver (after the matcher auto-assigns the FK)
     *
     * This is identical to repay() except it does NOT create a new Transaction —
     * the caller is responsible for that. It only advances schedule rows and recomputes
     * outstanding for the target line.
     */
    public function applyToSchedule(TransactionExt $tran, AccountCreditLine $line): void
    {
        if ($tran->type !== TransactionExt::TYPE_REPAY) {
            return;
        }
        DB::transaction(function () use ($tran, $line) {
            DB::table('account_credit_lines')->where('id', $line->id)->lockForUpdate()->first();

            $this->applyToScheduleRows($line, $tran, round((float) $tran->shares, 4));

            $outstanding = $this->calculator->recomputeForLine($line);

            if ($outstanding <= 0) {
                $line->status = AccountCreditLineExt::STATUS_PAID_OFF;
                $line->outstanding_shares = 0;
                $line->save();
            }

            /** @var AccountExt $account */
            $account = $line->account()->first();
            if ($account) {
                $this->calculator->updateAggregateBorBalance(
                    $account,
                    \Carbon\Carbon::parse($tran->timestamp ?? \Carbon\Carbon::today())->toDateString(),
                    $tran->id
                );
            }

            // Record the new outstanding for historical receivable reconstruction.
            $line->refresh();
            $this->balanceTracker->recordChange(
                $line,
                (float) $line->outstanding_shares,
                $tran,
                \Carbon\Carbon::parse($tran->timestamp ?? \Carbon\Carbon::today())
            );
        });
    }

    /**
     * ScheduleAdvancer contract: invoked by MatchResolutionService when a manual
     * resolution or auto-match has just bound `account_credit_line_id` on a REP.
     */
    public function advance(TransactionExt $tran, AccountCreditLine $line): void
    {
        $this->applyToSchedule($tran, $line);
    }

    /**
     * Apply the repayment amount to CreditLinePayment rows in due-date order.
     *
     * Rules:
     * - Apply shares to the earliest open (scheduled/partial/late) rows first.
     * - If the row's shares_due is fully covered → status = paid; set paid_transaction_id.
     * - If partial coverage → status = partial; leftover balance moves to next row.
     * - If extra (more than all open rows) → remaining is over-payment; all rows paid; line paid_off.
     */
    private function applyToScheduleRows(AccountCreditLine $line, TransactionExt $repTransaction, float $remainingShares): void
    {
        $openStatuses = [
            CreditLinePayment::STATUS_SCHEDULED,
            CreditLinePayment::STATUS_PARTIAL,
            CreditLinePayment::STATUS_LATE,
        ];

        $rows = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->whereIn('status', $openStatuses)
            ->orderBy('due_date', 'asc')
            ->get();

        foreach ($rows as $row) {
            if ($remainingShares <= 0) {
                break;
            }

            // How much of this row has already been credited by prior partial payments?
            $alreadyPaid = $this->sharesAlreadyPaidOnRow($row);
            $rowRemaining = round($row->shares_due - $alreadyPaid, 4);

            if ($rowRemaining <= 0) {
                // Row was already fully covered by a prior partial; close it.
                $row->status             = CreditLinePayment::STATUS_PAID;
                $row->paid_transaction_id = $repTransaction->id;
                $row->save();
                continue;
            }

            if ($remainingShares >= $rowRemaining) {
                // This payment fully covers the remaining balance on this row.
                $remainingShares -= $rowRemaining;
                $row->status              = CreditLinePayment::STATUS_PAID;
                $row->paid_transaction_id = $repTransaction->id;
                $row->save();
            } else {
                // Partial payment: covers some but not all of this row.
                $row->status              = CreditLinePayment::STATUS_PARTIAL;
                $row->paid_transaction_id = $repTransaction->id;
                $row->save();
                $remainingShares = 0;
            }
        }
    }

    /**
     * Sum shares already credited against a payment row by prior REP transactions.
     *
     * We identify prior transactions by looking at REP transactions that targeted this row
     * (i.e. they share the same credit line and have a timestamp before the current repayment).
     * Since a row can only have one paid_transaction_id, for partial rows we sum the shares
     * of the single transaction that set the partial status.
     *
     * Simplified model: each payment row tracks only its latest partial transaction.
     * If more complex partial tracking is needed, a separate partial-credit ledger would be required.
     * For now: if status=partial and paid_transaction_id is set, credit those shares.
     */
    private function sharesAlreadyPaidOnRow(CreditLinePayment $row): float
    {
        if ($row->status !== CreditLinePayment::STATUS_PARTIAL || !$row->paid_transaction_id) {
            return 0.0;
        }

        $tran = TransactionExt::find($row->paid_transaction_id);
        return $tran ? (float) $tran->shares : 0.0;
    }
}
