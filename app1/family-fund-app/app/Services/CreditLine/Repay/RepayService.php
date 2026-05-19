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
        private ?PaymentAllocator $allocator = null,
    ) {
        $this->balanceTracker = $this->balanceTracker ?? new CreditLineBalanceTracker();
        $this->allocator = $this->allocator ?? new PaymentAllocator();
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

            // Distribute shares onto the schedule (oldest open row first).
            $this->allocator->allocate($repTransaction, $line);

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

            // Satisfy the targeted row first; cascade any overflow onto the
            // oldest open rows. The allocation ledger records exactly which
            // shares of this tx landed on which row, so a partial row can be
            // co-funded by several payments without orphaning any of them.
            $row->refresh();
            $this->allocator->allocate($repTransaction, $line, $row);

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

            $this->allocator->allocate($tran, $line);

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
}
