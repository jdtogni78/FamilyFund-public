<?php

namespace App\Services\CreditLine\Repay;

use App\Models\AccountCreditLine;
use App\Models\CreditLinePayment;
use App\Models\CreditLinePaymentAllocation;
use App\Models\TransactionExt;
use Carbon\Carbon;

/**
 * PaymentAllocator — single owner of "how a REP transaction's shares are
 * distributed across credit-line schedule rows".
 *
 * The allocation ledger (credit_line_payment_allocations) is the unit of
 * truth. A row's `status` and `paid_transaction_id` are *derived* from its
 * allocations, never authored directly. One transaction may allocate across
 * many rows; one row may receive allocations from many transactions.
 *
 * `outstanding_shares` is intentionally NOT computed here — it stays
 * transaction-based in OutstandingCalculator (sum BOR − REP), independent of
 * how shares land on rows.
 *
 * Phase A: this service is exercised in isolation + by the backfill. Write
 * paths (RepayService/ReverseService) are wired to it in Phase B.
 */
class PaymentAllocator
{
    /** Statuses that can still receive an allocation. */
    private const OPEN_STATUSES = [
        CreditLinePayment::STATUS_SCHEDULED,
        CreditLinePayment::STATUS_PARTIAL,
        CreditLinePayment::STATUS_LATE,
    ];

    /** Rounding tolerance for "row fully covered" comparisons. */
    private const EPS = 1e-4;

    /**
     * Distribute a REP transaction's (still-unallocated) shares onto the
     * line's schedule rows.
     *
     * @param  TransactionExt        $tran        The REP transaction.
     * @param  AccountCreditLine     $line        The target line.
     * @param  CreditLinePayment|null $targetRow  Settle this row first, then cascade.
     * @param  array<int,float>|null $manualSplit [rowId => shares] explicit split (overrides auto).
     */
    public function allocate(
        TransactionExt $tran,
        AccountCreditLine $line,
        ?CreditLinePayment $targetRow = null,
        ?array $manualSplit = null
    ): void {
        if ($manualSplit !== null) {
            $touched = [];
            foreach ($manualSplit as $rowId => $shares) {
                $shares = round((float) $shares, 4);
                if ($shares <= 0) {
                    continue;
                }
                $this->recordAllocation((int) $rowId, $tran, $shares);
                $touched[(int) $rowId] = true;
            }
            foreach (array_keys($touched) as $rowId) {
                if ($row = CreditLinePayment::find($rowId)) {
                    $this->deriveRowStatus($row);
                }
            }
            return;
        }

        $remaining = round(
            (float) $tran->shares - $this->sharesAllocatedForTran($tran),
            4
        );
        if ($remaining <= 0) {
            return;
        }

        // Targeted row first.
        if ($targetRow) {
            $remaining = $this->fillRow($targetRow, $tran, $remaining);
        }

        // Cascade the remainder onto the oldest still-open rows.
        if ($remaining > 0) {
            $rows = CreditLinePayment::where('account_credit_line_id', $line->id)
                ->whereIn('status', self::OPEN_STATUSES)
                ->orderBy('due_date', 'asc')
                ->get();

            foreach ($rows as $row) {
                if ($remaining <= 0) {
                    break;
                }
                if ($targetRow && $row->id === $targetRow->id) {
                    continue; // already handled above
                }
                $remaining = $this->fillRow($row, $tran, $remaining);
            }
        }

        // Any leftover is a principal prepayment: deliberately recorded as no
        // row allocation. It still reduces outstanding via OutstandingCalculator.
    }

    /**
     * Apply up to `$remaining` shares of `$tran` to `$row`, capped at the
     * row's unallocated balance. Returns the still-unapplied remainder.
     */
    private function fillRow(CreditLinePayment $row, TransactionExt $tran, float $remaining): float
    {
        $row->refresh();

        if ($row->status === CreditLinePayment::STATUS_CANCELLED) {
            return $remaining;
        }

        $rowRemaining = round((float) $row->shares_due - $this->sharesAllocatedOnRow($row), 4);
        if ($rowRemaining <= 0) {
            $this->deriveRowStatus($row);
            return $remaining;
        }

        $apply = round(min($remaining, $rowRemaining), 4);
        if ($apply <= 0) {
            return $remaining;
        }

        $this->recordAllocation($row->id, $tran, $apply);
        $this->deriveRowStatus($row);

        return round($remaining - $apply, 4);
    }

    private function recordAllocation(int $rowId, TransactionExt $tran, float $shares): void
    {
        CreditLinePaymentAllocation::create([
            'credit_line_payment_id' => $rowId,
            'transaction_id'         => $tran->id,
            'shares'                 => $shares,
        ]);
    }

    /** Total shares allocated to a row across all transactions. */
    public function sharesAllocatedOnRow(CreditLinePayment $row): float
    {
        return round(
            (float) CreditLinePaymentAllocation::where('credit_line_payment_id', $row->id)->sum('shares'),
            4
        );
    }

    /** Total shares of a transaction already allocated to rows. */
    public function sharesAllocatedForTran(TransactionExt $tran): float
    {
        return round(
            (float) CreditLinePaymentAllocation::where('transaction_id', $tran->id)->sum('shares'),
            4
        );
    }

    /**
     * Recompute a row's denormalised `status` + `paid_transaction_id` from its
     * allocations. Cancelled rows are left untouched.
     */
    public function deriveRowStatus(CreditLinePayment $row): void
    {
        if ($row->status === CreditLinePayment::STATUS_CANCELLED) {
            return;
        }

        $allocated = $this->sharesAllocatedOnRow($row);
        $due       = round((float) $row->shares_due, 4);

        if ($allocated <= 0) {
            $row->status = $this->isPastDue($row)
                ? CreditLinePayment::STATUS_LATE
                : CreditLinePayment::STATUS_SCHEDULED;
            $row->paid_transaction_id = null;
            $row->save();
            return;
        }

        // Cosmetic representative tx: the largest contributing allocation.
        $top = CreditLinePaymentAllocation::where('credit_line_payment_id', $row->id)
            ->orderByDesc('shares')
            ->orderByDesc('id')
            ->first();
        $row->paid_transaction_id = $top?->transaction_id;

        $row->status = ($allocated + self::EPS >= $due)
            ? CreditLinePayment::STATUS_PAID
            : CreditLinePayment::STATUS_PARTIAL;
        $row->save();
    }

    /**
     * Remove all of a transaction's allocations and re-derive every row it
     * touched. Used when a REP transaction is reversed.
     */
    public function deallocate(TransactionExt $tran): void
    {
        $rowIds = CreditLinePaymentAllocation::where('transaction_id', $tran->id)
            ->pluck('credit_line_payment_id')
            ->unique()
            ->all();

        CreditLinePaymentAllocation::where('transaction_id', $tran->id)->delete();

        foreach ($rowIds as $rowId) {
            if ($row = CreditLinePayment::find($rowId)) {
                $this->deriveRowStatus($row);
            }
        }
    }

    private function isPastDue(CreditLinePayment $row): bool
    {
        return Carbon::parse($row->due_date)->lt(Carbon::today());
    }
}
