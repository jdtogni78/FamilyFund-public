<?php

namespace App\Services\CreditLine\Adjust;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\CreditLineAdjustment;
use App\Models\CreditLinePayment;
use App\Models\UserExt;
use App\Services\CreditLine\Exceptions\NoChangeException;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates a credit-line readjustment (UC-09, UC-10, UC-43).
 *
 * Sequence (all inside a single DB transaction):
 *  1. Guard: if term, frequency and start date are all unchanged, throw NoChangeException.
 *  2. Capture old values and compute oldPlannedPayoffDate.
 *  3. Recompute outstanding_shares from BOR/REP transactions via OutstandingCalculator.
 *  4. Apply new values to the line (term_months, payment_frequency, maturity_date).
 *  5. Cancel `scheduled` CreditLinePayment rows due on/after the effective
 *     date (do NOT delete). Rows due before it stay in force — the old plan
 *     governs until the new schedule starts (matters for forward-dating).
 *  6. Generate fresh schedule via AmortizationScheduleBuilder, anchored to the
 *     caller-supplied effective date (defaults to today).
 *  7. Write an immutable CreditLineAdjustment audit row.
 *
 * History preservation: old schedule rows are cancelled, never deleted, and each
 * adjustment is an append-only audit row that now also records the effective
 * (start) date used. ScheduleSnapshotBuilder keys snapshots off `created_at`
 * (wall-clock write time), which is independent of the chosen effective date —
 * so backdating/forward-dating the new schedule never rewrites what an earlier
 * point in history showed.
 *
 * Phase 2 dependency: Authorization (admin-only) is enforced at the controller layer,
 * not here. This service is intentionally callable from any context.
 */
class ReadjustService
{
    public function __construct(
        private readonly OutstandingCalculator $outstandingCalculator,
        private readonly AmortizationScheduleBuilder $scheduleBuilder,
    ) {}

    /**
     * Readjust a credit line's term and/or payment frequency.
     *
     * @param  AccountCreditLine  $line           The line to readjust.
     * @param  int|null           $newTermMonths  New term (null = keep current).
     * @param  string|null        $newFrequency   New frequency (null = keep current).
     * @param  UserExt|null       $adminUser      Who triggered it (null = system).
     * @param  string|null        $reason         Optional free-text reason.
     * @param  Carbon|null        $effectiveDate  Start date the new schedule is
     *                                             anchored to (null = today).
     * @return CreditLineAdjustment               The written audit row.
     *
     * @throws NoChangeException When term, frequency and start date are all unchanged.
     */
    public function readjust(
        AccountCreditLine $line,
        ?int $newTermMonths,
        ?string $newFrequency,
        ?UserExt $adminUser,
        ?string $reason = null,
        ?Carbon $effectiveDate = null
    ): CreditLineAdjustment {
        // Resolve effective new values, defaulting to current.
        $effectiveNewTerm      = $newTermMonths ?? $line->term_months;
        $effectiveNewFrequency = $newFrequency  ?? $line->payment_frequency;
        $startDate             = ($effectiveDate ?? Carbon::today())->copy()->startOfDay();

        // Guard: no-op check. A caller-supplied start date is itself an
        // intentional reschedule (it re-anchors every future payment), so a
        // bare re-date is allowed through even when term/frequency are equal.
        if ($effectiveDate === null
            && $effectiveNewTerm === $line->term_months
            && $effectiveNewFrequency === $line->payment_frequency) {
            throw new NoChangeException();
        }

        return DB::transaction(function () use (
            $line,
            $effectiveNewTerm,
            $effectiveNewFrequency,
            $adminUser,
            $reason,
            $startDate
        ) {
            // Step 1 — Capture old values.
            $oldTermMonths       = $line->term_months;
            $oldFrequency        = $line->payment_frequency;
            $oldMaturityDate     = Carbon::parse($line->maturity_date);

            // oldPlannedPayoffDate = max due_date of current scheduled rows, or maturity_date.
            $lastScheduled = CreditLinePayment::where('account_credit_line_id', $line->id)
                ->where('status', CreditLinePayment::STATUS_SCHEDULED)
                ->orderByDesc('due_date')
                ->first();

            $oldPlannedPayoffDate = $lastScheduled
                ? Carbon::parse($lastScheduled->due_date)
                : $oldMaturityDate->copy();

            // Step 2 — Recompute outstanding_shares.
            $outstandingShares = $this->outstandingCalculator->recomputeForLine($line);

            // Step 3 — Apply new values to the line.
            $line->term_months       = $effectiveNewTerm;
            $line->payment_frequency = $effectiveNewFrequency;
            $line->maturity_date     = $startDate->copy()->addMonths($effectiveNewTerm)->toDateString();
            $line->save();

            // Step 4 — Cancel existing scheduled payments that the new schedule
            // supersedes (preserve them, never delete). Only rows due *on or
            // after* the effective date are cancelled: when the new plan is
            // forward-dated, the old plan stays in force until it starts, so
            // installments due before the effective date must remain scheduled
            // (otherwise they vanish, leaving a gap with no obligation in the
            // pre-effective-date window).
            CreditLinePayment::where('account_credit_line_id', $line->id)
                ->where('status', CreditLinePayment::STATUS_SCHEDULED)
                ->whereDate('due_date', '>=', $startDate->toDateString())
                ->update(['status' => CreditLinePayment::STATUS_CANCELLED]);

            // Step 5 — Generate fresh schedule anchored to the effective date.
            $newPayments = $this->scheduleBuilder->build($line, $outstandingShares, $startDate);

            // Compute new planned payoff date from the last new payment row.
            $newPlannedPayoffDate = !empty($newPayments)
                ? Carbon::parse(end($newPayments)->due_date)
                : Carbon::parse($line->maturity_date);

            // Step 6 — Write immutable adjustment audit row.
            $adjustment = CreditLineAdjustment::create([
                'account_credit_line_id'          => $line->id,
                'adjusted_at'                     => now(),
                'effective_date'                  => $startDate->toDateString(),
                'adjusted_by_user_id'             => $adminUser?->id,
                'outstanding_shares_at_adjustment'=> $outstandingShares,
                'old_term_months'                 => $oldTermMonths,
                'new_term_months'                 => $effectiveNewTerm,
                'old_payment_frequency'           => $oldFrequency,
                'new_payment_frequency'           => $effectiveNewFrequency,
                'old_maturity_date'               => $oldMaturityDate->toDateString(),
                'new_maturity_date'               => $line->maturity_date,
                'old_planned_payoff_date'         => $oldPlannedPayoffDate->toDateString(),
                'new_planned_payoff_date'         => $newPlannedPayoffDate->toDateString(),
                'reason'                          => $reason,
            ]);

            return $adjustment;
        });
    }
}
