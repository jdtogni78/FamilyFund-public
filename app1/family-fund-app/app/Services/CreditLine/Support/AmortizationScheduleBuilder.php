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

            // Last payment absorbs decimal rounding remainder.
            if ($i === $nPayments) {
                $sharesDue = round($basePayment + $remainder, 4);
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
     * quarterly: n = term_months / 3   (must be exact integer)
     * annual:    n = term_months / 12  (must be exact integer)
     */
    public function computeNPayments(int $termMonths, string $frequency): int
    {
        switch ($frequency) {
            case AccountCreditLineExt::FREQUENCY_MONTHLY:
                return $termMonths;
            case AccountCreditLineExt::FREQUENCY_QUARTERLY:
                return (int) round($termMonths / 3);
            case AccountCreditLineExt::FREQUENCY_ANNUAL:
                return (int) round($termMonths / 12);
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
