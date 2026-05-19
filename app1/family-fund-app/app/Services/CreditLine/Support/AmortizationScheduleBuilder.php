<?php

namespace App\Services\CreditLine\Support;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\CreditLinePayment;
use Carbon\Carbon;

/**
 * Builds and persists an amortization schedule for a credit line.
 *
 * Design notes:
 * - Even-split: shares_per_payment = outstanding / n_payments, rounded to 4 d.p.
 * - The last payment absorbs the rounding remainder so sum == outstanding_shares exactly.
 * - First due_date is fromDate + 1 period.
 * - Periods per frequency: monthly=1 month, quarterly=3 months, annual=12 months.
 * - Designed to be reused by wave 1c (AdjustService) — accepts arbitrary fromDate and outstanding.
 *
 * Non-divisible terms (2026-05-19 schedule-correctness audit):
 *   A term need not divide evenly by the frequency period (e.g. a 10-month
 *   quarterly or 14-month annual line). computeNPayments() therefore rounds
 *   the count *up* (ceil) so the whole term is always covered — never leave a
 *   stretch of the term with no scheduled obligation — and the final payment's
 *   due_date is pinned to maturity (fromDate + term_months) so the extra,
 *   partial period does not overshoot past maturity. This keeps the schedule
 *   consistent with AccountCreditLine::maturity_date (which ReadjustService
 *   sets to startDate + term_months) for *every* term, divisible or not.
 *   For exact-divisible and monthly terms period*n == term_months, so the pin
 *   is a no-op and the schedule is byte-identical to the pre-audit behaviour.
 *   Invariants preserved: Σ shares_due == outstanding exactly (last row
 *   absorbs the remainder); due_date series strictly increasing; last
 *   due_date == maturity_date.
 */
class AmortizationScheduleBuilder
{
    /**
     * Build and persist a schedule for the given credit line.
     *
     * @param  AccountCreditLine $line              The line to build a schedule for.
     * @param  float             $outstandingShares The outstanding share amount to amortise.
     * @param  Carbon            $fromDate          The anchor date; first payment falls 1 period after this.
     * @return CreditLinePayment[]                  The newly created (persisted) payment rows.
     */
    public function build(AccountCreditLine $line, float $outstandingShares, Carbon $fromDate): array
    {
        $nPayments = $this->computeNPayments($line->term_months, $line->payment_frequency);

        if ($nPayments <= 0) {
            return [];
        }

        $basePayment  = round($outstandingShares / $nPayments, 4);
        $totalRounded = round($basePayment * $nPayments, 4);
        $remainder    = round($outstandingShares - $totalRounded, 4);

        $periodMonths = $this->periodMonths($line->payment_frequency);
        $payments     = [];

        for ($i = 1; $i <= $nPayments; $i++) {
            $dueDate      = $fromDate->copy()->addMonths($periodMonths * $i);
            $sharesDue    = $basePayment;

            // Last payment absorbs the decimal rounding remainder and is
            // pinned to maturity so a non-divisible term ends exactly at
            // fromDate + term_months instead of overshooting on the final,
            // partial period (no-op when period*n == term_months).
            if ($i === $nPayments) {
                $sharesDue = round($basePayment + $remainder, 4);
                $dueDate   = $fromDate->copy()->addMonths($line->term_months);
            }

            $payment = CreditLinePayment::create([
                'account_credit_line_id' => $line->id,
                'due_date'               => $dueDate->toDateString(),
                'shares_due'             => $sharesDue,
                'status'                 => CreditLinePayment::STATUS_SCHEDULED,
                'paid_transaction_id'    => null,
            ]);

            $payments[] = $payment;
        }

        return $payments;
    }

    /**
     * Compute the number of payments given a term in months and a frequency string.
     *
     * monthly:   n = term_months × 1
     * quarterly: n = ceil(term_months / 3)
     * annual:    n = ceil(term_months / 12)
     *
     * The count is rounded *up* so a non-divisible term is always fully
     * covered (a 10-month quarterly line gets 4 payments, not 3; a 14-month
     * annual line gets 2, not 1). The builder pins the final payment to
     * maturity so the extra partial period does not overshoot.
     */
    public function computeNPayments(int $termMonths, string $frequency): int
    {
        switch ($frequency) {
            case AccountCreditLineExt::FREQUENCY_MONTHLY:
                return $termMonths;
            case AccountCreditLineExt::FREQUENCY_QUARTERLY:
                return (int) ceil($termMonths / 3);
            case AccountCreditLineExt::FREQUENCY_ANNUAL:
                return (int) ceil($termMonths / 12);
            default:
                return $termMonths; // fallback to monthly
        }
    }

    /**
     * How many calendar months elapse between consecutive payments.
     */
    public function periodMonths(string $frequency): int
    {
        switch ($frequency) {
            case AccountCreditLineExt::FREQUENCY_QUARTERLY:
                return 3;
            case AccountCreditLineExt::FREQUENCY_ANNUAL:
                return 12;
            default: // monthly
                return 1;
        }
    }
}
