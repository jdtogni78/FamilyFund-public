<?php

use App\Models\AccountCreditLine;
use App\Models\CreditLinePayment;
use App\Models\CreditLinePaymentAllocation;
use App\Models\TransactionExt;
use App\Services\CreditLine\Repay\PaymentAllocator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the allocation ledger from existing schedule state.
 *
 * Per line, deterministically replays the line's applied REP transactions:
 *
 *   Step A — explicit targets: for each REP tx, allocate to the row(s) that
 *            currently point at it via paid_transaction_id, capped at the
 *            tx's and row's remaining balance.
 *   Step B — cascade leftovers: any still-unallocated portion of a tx
 *            (historical overflow / over-payment) cascades onto the oldest
 *            still-open rows, exactly as a payment would going forward.
 *
 * This reproduces healthy lines identically AND self-heals the credit-line-2
 * corruption: tx#1215's 16.67 overflow is re-applied to row #5 alongside
 * tx#1214's 50, so row #5 correctly shows 66.67/83.33 partial from two txs.
 *
 * `outstanding_shares` is transaction-based (OutstandingCalculator) and is
 * intentionally NOT touched here — it must be byte-identical afterward.
 *
 * Idempotent: a line that already has any allocation is skipped, so
 * re-running `migrate` is safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        /** @var PaymentAllocator $allocator */
        $allocator = app(PaymentAllocator::class);

        AccountCreditLine::query()->orderBy('id')->chunkById(100, function ($lines) use ($allocator) {
            foreach ($lines as $line) {
                $this->backfillLine($line, $allocator);
            }
        });
    }

    private function backfillLine(AccountCreditLine $line, PaymentAllocator $allocator): void
    {
        $paymentIds = CreditLinePayment::where('account_credit_line_id', $line->id)->pluck('id');
        if ($paymentIds->isEmpty()) {
            return;
        }

        // Idempotency: skip lines already backfilled.
        $hasAllocations = CreditLinePaymentAllocation::whereIn('credit_line_payment_id', $paymentIds)->exists();
        if ($hasAllocations) {
            return;
        }

        // Applied REP transactions only — same filter OutstandingCalculator uses.
        $reps = TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_REPAY)
            ->where('reversed', false)
            ->where(function ($q) {
                $q->whereNull('credit_line_match_status')
                  ->orWhereIn('credit_line_match_status', [
                      TransactionExt::MATCH_STATUS_AUTO_MATCHED,
                      TransactionExt::MATCH_STATUS_MANUAL,
                  ]);
            })
            ->orderBy('timestamp')
            ->orderBy('id')
            ->get();

        if ($reps->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($line, $reps, $allocator) {
            // Step A: explicit targets (rows currently linked to each tx).
            foreach ($reps as $tran) {
                $linkedRows = CreditLinePayment::where('account_credit_line_id', $line->id)
                    ->where('paid_transaction_id', $tran->id)
                    ->orderBy('due_date')
                    ->get();

                foreach ($linkedRows as $row) {
                    $tranRemaining = round(
                        (float) $tran->shares - $allocator->sharesAllocatedForTran($tran),
                        4
                    );
                    if ($tranRemaining <= 0) {
                        break;
                    }
                    $rowRemaining = round(
                        (float) $row->shares_due - $allocator->sharesAllocatedOnRow($row),
                        4
                    );
                    $apply = round(min($tranRemaining, $rowRemaining), 4);
                    if ($apply <= 0) {
                        continue;
                    }
                    $allocator->allocate($tran, $line, null, [$row->id => $apply]);
                }
            }

            // Step B: cascade any unallocated remainder oldest-open-first.
            foreach ($reps as $tran) {
                $allocator->allocate($tran, $line);
            }

            // Re-derive every row so denormalised status/paid_transaction_id
            // reflect the ledger (no-op for rows already consistent).
            foreach (CreditLinePayment::where('account_credit_line_id', $line->id)->get() as $row) {
                $allocator->deriveRowStatus($row);
            }
        });
    }

    public function down(): void
    {
        // Allocation rows are dropped by the create-table migration's down().
        // Derived status/paid_transaction_id are left as-is (they reflect the
        // ledger, which is the correct state).
        CreditLinePaymentAllocation::query()->delete();
    }
};
