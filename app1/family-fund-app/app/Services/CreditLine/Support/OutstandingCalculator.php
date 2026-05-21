<?php

namespace App\Services\CreditLine\Support;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\AccountExt;
use App\Models\TransactionExt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes outstanding and available-to-borrow shares for credit lines.
 *
 * Rule summary (from plan §5 rule 8):
 *   - Transactions with credit_line_match_status IN (NULL, 'auto_matched', 'manual') are counted.
 *   - Transactions with credit_line_match_status IN ('ambiguous', 'unmatched') are IGNORED
 *     because they are not yet assigned to a line.
 */
class OutstandingCalculator
{
    /**
     * Recompute outstanding_shares for a single line from its BOR/REP transactions
     * and persist the updated value on the model.
     *
     * Counted statuses: NULL, auto_matched, manual.
     * Ignored statuses: ambiguous, unmatched.
     *
     * @return float The recomputed outstanding shares (rounded to 4 d.p.).
     */
    public function recomputeForLine(AccountCreditLine $line, bool $includeReversed = false): float
    {
        $query = TransactionExt::where('account_credit_line_id', $line->id)
            ->whereIn('type', [TransactionExt::TYPE_BORROW, TransactionExt::TYPE_REPAY])
            ->where(function ($q) {
                $q->whereNull('credit_line_match_status')
                  ->orWhereIn('credit_line_match_status', [
                      TransactionExt::MATCH_STATUS_AUTO_MATCHED,
                      TransactionExt::MATCH_STATUS_MANUAL,
                  ]);
            });

        if (!$includeReversed) {
            $query->where('reversed', false);
        }

        $transactions = $query->get();

        $outstanding = 0.0;
        foreach ($transactions as $tran) {
            if ($tran->type === TransactionExt::TYPE_BORROW) {
                $outstanding += (float) $tran->shares;
            } elseif ($tran->type === TransactionExt::TYPE_REPAY) {
                $outstanding -= (float) $tran->shares;
            }
        }

        $outstanding = round($outstanding, 4);
        $line->outstanding_shares = $outstanding;
        $line->save();

        return $outstanding;
    }

    /**
     * Calculate how many shares this account can still borrow.
     *
     * Formula:
     *   available = AccountExt::sharesAsOf($asOf) − Σ outstanding_shares across all ACTIVE lines
     *
     * Explanation of the accounting:
     *   AccountExt::sharesAsOf() already returns OWN_shares − BOR_aggregate_balance.
     *   The BOR_aggregate_balance row equals Σ outstanding_shares on ALL lines.
     *   So:
     *       sharesAsOf() = OWN − BOR_aggregate = OWN − Σ_outstanding_all_lines
     *
     *   But "available to borrow" should be:
     *       OWN − Σ_outstanding_active_lines
     *
     *   Since BOR_aggregate = Σ_outstanding_all_lines (including paid_off, cancelled),
     *   but in practice paid_off lines have outstanding=0 and cancelled lines have outstanding=0,
     *   Σ_outstanding_all_lines ≈ Σ_outstanding_active_lines.
     *
     *   We compute it directly and safely as:
     *       available = sharesAsOf($asOf) − additional check for any unpaid active lines
     *
     *   The simplest correct implementation: read OWN balance directly (before BOR offset),
     *   then subtract Σ outstanding_shares of active lines.
     *
     *   OWN_shares = sharesAsOf($asOf) + BOR_aggregate
     *   available  = OWN_shares − Σ_outstanding_active_lines
     *              = (sharesAsOf($asOf) + BOR_aggregate) − Σ_outstanding_active_lines
     *
     *   When the aggregate BOR row = Σ_outstanding_active_lines, this simplifies to sharesAsOf().
     *   We use the explicit computation to be safe.
     *
     * @param  AccountExt $account
     * @param  Carbon|null $asOf   Defaults to today.
     * @return float Available shares to borrow (may be negative if over-drawn).
     */
    public function availableToBorrow(AccountExt $account, ?Carbon $asOf = null): float
    {
        $asOf = $asOf ?? Carbon::today();

        // Get OWN balance directly (before BOR offset).
        $allBalances  = $account->allSharesAsOf($asOf->toDateString());
        $ownShares    = isset($allBalances['OWN']) ? (float) $allBalances['OWN']->shares : 0.0;

        // Sum outstanding_shares on all ACTIVE lines for this account.
        $activeOutstanding = (float) AccountCreditLine::where('account_id', $account->id)
            ->where('status', AccountCreditLineExt::STATUS_ACTIVE)
            ->sum('outstanding_shares');

        return round($ownShares - $activeOutstanding, 4);
    }

    /**
     * Update the aggregate BOR AccountBalance row for the account.
     *
     * The system keeps exactly one BOR balance row per account (per §5 rule 8).
     * This method recalculates the total from all outstanding lines and upserts the row.
     *
     * @param  AccountExt $account
     * @param  string     $asOf    Date string for balance start_dt (usually today).
     * @param  int        $triggeringTransactionId  The BOR/REP transaction that caused the update.
     * @param  bool       $rejectBackdate  When true (Draw path), a backdated
     *         settlement date is honoured by splicing the change into the
     *         BOR balance chain — every row overlapping [$asOf, ∞) is bumped
     *         by the delta and any gap from $asOf forward is filled with a
     *         closed historical row. When false (Repay path: routine
     *         late-payment reconciliation) the backdate is loosely clamped
     *         forward to the open row's start_dt instead.
     */
    public function updateAggregateBorBalance(
        AccountExt $account,
        string $asOf,
        int $triggeringTransactionId,
        bool $rejectBackdate = false
    ): void {
        $totalOutstanding = (float) AccountCreditLine::where('account_id', $account->id)
            ->whereIn('status', [AccountCreditLineExt::STATUS_ACTIVE])
            ->sum('outstanding_shares');

        $totalOutstanding = round($totalOutstanding, 4);

        // Find existing BOR balance row (open-ended: end_dt = '9999-12-31').
        $existing = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->whereDate('end_dt', '9999-12-31')
            ->first();

        if ($existing && $existing->start_dt && $asOf < $existing->start_dt->toDateString()) {
            if ($rejectBackdate) {
                // Draw path: splice the new draw's contribution into the BOR
                // chain so as-of reads of the period [$asOf, $existing.start_dt)
                // see the historically-correct outstanding total. The delta
                // is exactly the new line's principal (the only contributor
                // to the aggregate that wasn't already reflected in the open
                // row's shares).
                $delta = round($totalOutstanding - (float) $existing->shares, 4);
                $this->spliceBackdatedBorDelta($account, $asOf, $triggeringTransactionId, $delta);
                return;
            }
            // Repay path: clamp forward. The aggregate BOR row is a single
            // open-ended "current state" projection (it always recomputes the
            // total from the *current* outstanding across active lines, not an
            // as-of reconstruction). Routine late-payment reconciliation
            // accepts a loose history — the REP transaction itself still
            // carries the admin's real settlement date, and the per-line
            // schedule reconciliation is unaffected.
            $asOf = $existing->start_dt->toDateString();
        }

        if ($totalOutstanding <= 0) {
            // No outstanding — close the BOR row if it exists.
            //
            // Wave-2 used to delete same-day rows ("zero-length, no value"),
            // but QA_BUGS_2026-05-20 #5 noted that this erased the audit
            // trail "this account was borrowed against on this date". The
            // row is now kept as a zero-length closed record: invisible to
            // asOf reads (start_dt<=now AND end_dt>now excludes it on
            // start_dt==end_dt==now) but discoverable via raw queries.
            if ($existing) {
                $existing->end_dt = $asOf;
                $existing->save();
            }
            return;
        }

        // Wave-2 review (2026-05-14): same-day double-event used to leave a
        // zero-length BOR row (start_dt=end_dt=today) plus a fresh open row.
        // If the open row already starts today, update it in place instead of
        // closing + re-opening — there's only ever one source of truth (the
        // sum across all active lines), so an in-place update keeps history
        // clean.
        if ($existing && $existing->start_dt && $existing->start_dt->toDateString() === $asOf) {
            $existing->shares = $totalOutstanding;
            $existing->transaction_id = $triggeringTransactionId;
            $existing->save();
            return;
        }

        if ($existing) {
            // Close old open row.
            $existing->end_dt = $asOf;
            $existing->save();
        }

        // Create new open BOR row.
        AccountBalance::create([
            'account_id'          => $account->id,
            'transaction_id'      => $triggeringTransactionId,
            'type'                => 'BOR',
            'shares'              => $totalOutstanding,
            'start_dt'            => $asOf,
            'previous_balance_id' => $existing?->id,
            'end_dt'              => '9999-12-31',
        ]);
    }

    /**
     * Splice a backdated draw's contribution ($delta shares) into the BOR
     * balance chain starting at $asOf. Every existing BOR row that overlaps
     * [$asOf, ∞) is bumped by $delta (a row straddling $asOf is split first),
     * and any uncovered period in [$asOf, openRow.start_dt) is filled with a
     * closed historical row.
     *
     * Called by updateAggregateBorBalance() when the Draw path lands a draw
     * before the currently-open BOR row's start_dt — splicing the chain
     * preserves the temporal invariant (end_dt > start_dt on every row) while
     * still reflecting the new draw at its real historical date.
     */
    private function spliceBackdatedBorDelta(
        AccountExt $account,
        string $asOf,
        int $triggeringTransactionId,
        float $delta
    ): void {
        if ($delta == 0.0) {
            return;
        }

        $rows = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->orderBy('start_dt', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Step 1: bump every row that overlaps [$asOf, ∞); split any row
        // whose range straddles $asOf so the "after" piece carries the bump.
        foreach ($rows as $row) {
            if (!$row->start_dt || !$row->end_dt) {
                continue;
            }
            $startStr = $row->start_dt->toDateString();
            $endStr   = $row->end_dt->toDateString();

            if ($endStr <= $asOf) {
                continue;
            }
            if ($startStr >= $asOf) {
                $row->shares = round((float) $row->shares + $delta, 4);
                $row->save();
                continue;
            }
            // Row straddles $asOf — split into [start, $asOf) and [$asOf, end).
            AccountBalance::create([
                'account_id'          => $row->account_id,
                'transaction_id'      => $triggeringTransactionId,
                'type'                => 'BOR',
                'shares'              => round((float) $row->shares + $delta, 4),
                'start_dt'            => $asOf,
                'end_dt'              => $endStr,
                'previous_balance_id' => $row->id,
            ]);
            $row->end_dt = $asOf;
            $row->save();
        }

        // Step 2: fill any uncovered period in [$asOf, openRow.start_dt) with
        // a closed historical row carrying just the new draw's contribution.
        $rowsAfter = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->whereDate('end_dt', '>', $asOf)
            ->orderBy('start_dt', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $cursor = $asOf;
        foreach ($rowsAfter as $row) {
            $startStr = $row->start_dt->toDateString();
            if ($startStr > $cursor) {
                AccountBalance::create([
                    'account_id'     => $account->id,
                    'transaction_id' => $triggeringTransactionId,
                    'type'           => 'BOR',
                    'shares'         => round($delta, 4),
                    'start_dt'       => $cursor,
                    'end_dt'         => $startStr,
                ]);
            }
            $endStr = $row->end_dt->toDateString();
            if ($endStr === '9999-12-31') {
                return;
            }
            $cursor = $endStr;
        }
    }
}
