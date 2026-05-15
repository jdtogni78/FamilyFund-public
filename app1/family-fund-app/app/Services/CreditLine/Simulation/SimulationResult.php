<?php

namespace App\Services\CreditLine\Simulation;

/**
 * Value object representing the result of a single credit-line payment
 * simulation scenario.
 *
 * Read-only — produced by PaymentSimulator::simulate(). No mutation of
 * any persisted state; this is purely a "what if" projection.
 *
 * @property-read int|null $payoff_month    Month index at which outstanding hit zero (1-based), or null if the cap was hit.
 * @property-read string|null $payoff_date  ISO date of payoff, or null if cap.
 * @property-read float $total_paid_usd     monthly_payment_usd × months_paid.
 * @property-read float $total_paid_shares  Sum of shares purchased over the run.
 * @property-read float $annual_growth_rate_pct   The growth rate scenario used (percent).
 * @property-read float $monthly_payment_usd      The hypothetical payment used.
 * @property-read float $starting_outstanding_shares
 * @property-read array $monthly_series     Array of step rows. Each row: [
 *     'month' => int, 'date' => 'Y-m-d', 'outstanding_shares' => float,
 *     'share_value' => float, 'shares_paid' => float, 'cumulative_shares_paid' => float,
 * ].
 * @property-read bool $capped              True if the run terminated at the 600-month safety cap.
 */
class SimulationResult
{
    public function __construct(
        public readonly ?int $payoff_month,
        public readonly ?string $payoff_date,
        public readonly float $total_paid_usd,
        public readonly float $total_paid_shares,
        public readonly float $annual_growth_rate_pct,
        public readonly float $monthly_payment_usd,
        public readonly float $starting_outstanding_shares,
        public readonly array $monthly_series,
        public readonly bool $capped,
    ) {}
}
