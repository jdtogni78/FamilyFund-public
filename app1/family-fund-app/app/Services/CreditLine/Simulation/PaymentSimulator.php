<?php

namespace App\Services\CreditLine\Simulation;

use App\Models\AccountCreditLine;
use App\Models\FundExt;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Credit-line payment simulator.
 *
 * Projects when a credit line would be paid off given a hypothetical
 * monthly USD payment and a fund growth-rate assumption. Three scenarios
 * are produced: conservative (expected × 0.8), expected, aggressive
 * (expected × 1.2). By default the expected rate is the account's fund
 * expected growth rate ({@see FundExt::getExpectedGrowthRate()}) — the same
 * rate the account and fund forecasts use — and the conservative/aggressive
 * multipliers match the convention used by ChartBaseTrait and
 * QuickChartService::generateForecastChart().
 *
 * Callers may override any of the three scenario rates (see $rateOverrides on
 * {@see resolveRates()}): supplying only `expected` re-scales the bands via the
 * ×0.8/×1.2 multipliers; supplying a band sets it verbatim.
 *
 * Read-only — never mutates the credit line, transactions, or balances.
 */
class PaymentSimulator
{
    /** Safety cap so a too-small payment doesn't loop forever. */
    public const MONTH_CAP = 600;

    /** Conservative growth multiplier (see ChartBaseTrait.php:301-310). */
    public const CONSERVATIVE_MULTIPLIER = 0.8;

    /** Aggressive growth multiplier (see ChartBaseTrait.php:301-310). */
    public const AGGRESSIVE_MULTIPLIER = 1.2;

    /**
     * Run a single scenario.
     *
     * @param  AccountCreditLine $line
     * @param  float             $monthlyPaymentUsd     Positive USD amount.
     * @param  float             $annualGrowthRatePct   Annual growth rate, as a percentage (e.g. 7.0 → 7%).
     * @param  float|null        $startShareValue       Override current share value (for tests). Defaults to today's share value.
     * @return SimulationResult
     *
     * @throws InvalidArgumentException When monthlyPaymentUsd <= 0.
     */
    public function simulate(
        AccountCreditLine $line,
        float $monthlyPaymentUsd,
        float $annualGrowthRatePct,
        ?float $startShareValue = null
    ): SimulationResult {
        if ($monthlyPaymentUsd <= 0) {
            throw new InvalidArgumentException('monthly_payment_usd must be > 0');
        }

        $outstanding = (float) $line->outstanding_shares;
        $startingOutstanding = $outstanding;

        // Resolve starting share value.
        if ($startShareValue !== null) {
            $shareValue = (float) $startShareValue;
        } else {
            $account = $line->account()->first();
            $shareValue = $account ? (float) $account->shareValueAsOf(Carbon::today()->toDateString()) : 1.0;
        }
        if ($shareValue <= 0) {
            $shareValue = 1.0;
        }

        $monthlyGrowthFactor = ($annualGrowthRatePct / 100.0 + 1.0) ** (1.0 / 12.0);

        // Anchor projected dates to today: the simulation starts from the
        // line's CURRENT outstanding and today's share value, so a seasoned
        // or backdated origination_date would otherwise produce payoff dates
        // in the past. The answer to "when is it paid off if I start paying
        // $X/month now" is today + payoff_month, not origination + month.
        $startDate = Carbon::today();

        $series = [];
        $cumulativeShares = 0.0;
        $cumulativeUsd = 0.0;
        $capped = false;
        $payoffMonth = null;

        for ($m = 1; $m <= self::MONTH_CAP; $m++) {
            $shareValue *= $monthlyGrowthFactor;
            $sharesPaid = round($monthlyPaymentUsd / $shareValue, 4);

            // Cap shares paid so we don't over-repay.
            $cappedThisMonth = false;
            if ($sharesPaid > $outstanding) {
                $sharesPaid = round($outstanding, 4);
                $cappedThisMonth = true;
            }

            // USD actually paid this month: the full monthly payment for a
            // normal month, but only the cost of the remaining shares on the
            // final (capped) month — otherwise total_paid_usd bills an
            // unspent fraction of the last payment and disagrees with the
            // (capped) total_paid_shares.
            $usdThisMonth = $cappedThisMonth
                ? round($sharesPaid * $shareValue, 2)
                : $monthlyPaymentUsd;
            $cumulativeUsd = round($cumulativeUsd + $usdThisMonth, 2);

            $outstanding = round($outstanding - $sharesPaid, 4);
            if ($outstanding < 0) {
                $outstanding = 0.0;
            }
            $cumulativeShares = round($cumulativeShares + $sharesPaid, 4);

            $series[] = [
                'month'                  => $m,
                'date'                   => $startDate->copy()->addMonths($m)->toDateString(),
                'outstanding_shares'     => $outstanding,
                'share_value'            => round($shareValue, 4),
                'shares_paid'            => $sharesPaid,
                'cumulative_shares_paid' => $cumulativeShares,
            ];

            if ($outstanding <= 0) {
                $payoffMonth = $m;
                break;
            }
        }

        if ($payoffMonth === null) {
            $capped = true;
        }

        $payoffDate = $payoffMonth !== null
            ? $startDate->copy()->addMonths($payoffMonth)->toDateString()
            : null;

        return new SimulationResult(
            payoff_month: $payoffMonth,
            payoff_date: $payoffDate,
            total_paid_usd: round($cumulativeUsd, 2),
            total_paid_shares: $cumulativeShares,
            annual_growth_rate_pct: $annualGrowthRatePct,
            monthly_payment_usd: $monthlyPaymentUsd,
            starting_outstanding_shares: $startingOutstanding,
            monthly_series: $series,
            capped: $capped,
        );
    }

    /**
     * Run all three scenarios for the line: conservative, expected, aggressive.
     *
     * Multipliers match the codebase-wide convention in
     * ChartBaseTrait::createLinearRegressionResponse() and
     * QuickChartService::generateForecastChart():
     *   conservative = expected × 0.8
     *   aggressive   = expected × 1.2
     *
     * @param  array<string,float|null>|null $rateOverrides  Optional per-scenario
     *         annual-rate overrides keyed 'conservative'/'expected'/'aggressive'.
     *         See {@see resolveRates()} for resolution semantics.
     * @return array<string,SimulationResult>  Keys: 'conservative', 'expected', 'aggressive'.
     */
    public function simulateAllScenarios(
        AccountCreditLine $line,
        float $monthlyPaymentUsd,
        ?array $rateOverrides = null
    ): array {
        if ($monthlyPaymentUsd <= 0) {
            throw new InvalidArgumentException('monthly_payment_usd must be > 0');
        }

        ['rates' => $rates, 'startShareValue' => $startShareValue] = $this->resolveScenarios($line, $rateOverrides);

        $out = [];
        foreach ($rates as $key => $rate) {
            $out[$key] = $this->simulate($line, $monthlyPaymentUsd, $rate, $startShareValue);
        }

        return $out;
    }

    /**
     * Resolve the three growth-rate scenarios (conservative / expected /
     * aggressive) for a line's account.
     *
     * The default expected rate is the account's fund expected growth rate
     * ({@see FundExt::getExpectedGrowthRate()}, default 7%) — the same rate
     * driving the account and fund forecasts — with the conservative/aggressive
     * bands derived via the ×0.8/×1.2 multipliers.
     *
     * $rateOverrides (keys 'conservative'/'expected'/'aggressive', each a float
     * annual percentage or null/absent for "use default") lets a caller override
     * the assumptions:
     *   - Overriding only `expected` re-scales BOTH bands from the new expected
     *     via the multipliers (so the ±20% spread follows the new anchor).
     *   - Overriding a band sets that band verbatim, leaving the others intact.
     *
     * @param  array<string,float|null>|null $rateOverrides
     * @return array<string,float>  Keys: 'conservative', 'expected', 'aggressive'.
     */
    public function resolveRates(AccountCreditLine $line, ?array $rateOverrides = null): array
    {
        $expected = 7.0;
        $account = $line->account()->first();
        if ($account) {
            /** @var FundExt|null $fund */
            $fund = $account->fund()->first();
            if ($fund) {
                $expected = (float) $fund->getExpectedGrowthRate();
            }
        }

        $rates = [
            'conservative' => $expected * self::CONSERVATIVE_MULTIPLIER,
            'expected'     => $expected,
            'aggressive'   => $expected * self::AGGRESSIVE_MULTIPLIER,
        ];

        return $this->applyRateOverrides($rates, $rateOverrides);
    }

    /**
     * Apply per-scenario rate overrides onto the resolved defaults.
     *
     * @param  array<string,float>            $rates      Resolved default rates.
     * @param  array<string,float|null>|null  $overrides
     * @return array<string,float>
     */
    private function applyRateOverrides(array $rates, ?array $overrides): array
    {
        if (empty($overrides)) {
            return $rates;
        }

        $has = static fn (string $k): bool => array_key_exists($k, $overrides) && $overrides[$k] !== null;

        // Overriding the expected anchor re-scales any band the caller didn't
        // pin explicitly, so the conservative/aggressive spread tracks it.
        if ($has('expected')) {
            $expected = (float) $overrides['expected'];
            $rates['expected'] = $expected;
            if (!$has('conservative')) {
                $rates['conservative'] = $expected * self::CONSERVATIVE_MULTIPLIER;
            }
            if (!$has('aggressive')) {
                $rates['aggressive'] = $expected * self::AGGRESSIVE_MULTIPLIER;
            }
        }
        if ($has('conservative')) {
            $rates['conservative'] = (float) $overrides['conservative'];
        }
        if ($has('aggressive')) {
            $rates['aggressive'] = (float) $overrides['aggressive'];
        }

        return $rates;
    }

    /**
     * Resolve the three growth-rate scenarios plus a single starting share value
     * shared by all three so they project off the same baseline.
     *
     * @param  array<string,float|null>|null $rateOverrides
     * @return array{rates: array<string,float>, startShareValue: float|null}
     */
    private function resolveScenarios(AccountCreditLine $line, ?array $rateOverrides = null): array
    {
        $rates = $this->resolveRates($line, $rateOverrides);

        $startShareValue = null;
        $account = $line->account()->first();
        if ($account) {
            try {
                $startShareValue = (float) $account->shareValueAsOf(Carbon::today()->toDateString());
            } catch (\Throwable $e) {
                $startShareValue = null;
            }
        }

        return [
            'rates' => $rates,
            'startShareValue' => $startShareValue,
        ];
    }

    /**
     * Solve for the monthly USD payment required to pay off the line in
     * `targetMonths` months under the given annual growth-rate assumption.
     *
     * Approach: binary search over payment USD bounded by [0.01, hi], where
     * hi = outstanding_shares * startShareValue * 10 (very generous — enough
     * to clear the line in one month even after growth). For each candidate
     * payment we run `simulate()` and inspect `payoff_month`:
     *   - payoff_month == target → return candidate.
     *   - payoff_month < target  → payment too high, search lower half.
     *   - payoff_month > target or null → payment too low, search upper half.
     *
     * Iteration cap: 60. If we never hit the exact target month, return the
     * smallest payment whose simulate() payoff_month <= target (i.e. pay off
     * a hair early rather than late). Tolerance: bracket shrinks below $0.01.
     *
     * Closed-form geometric-series solution was considered but rejected: the
     * `simulate()` core rounds shares_paid and outstanding to 4 decimals per
     * month and caps the final monthly shares_paid at the remaining
     * outstanding. Those discretisations break the smooth analytic formula
     * and the binary search converges quickly anyway (≤60 evals).
     *
     * @throws InvalidArgumentException When targetMonths <= 0.
     */
    public function solveForPayment(
        AccountCreditLine $line,
        int $targetMonths,
        float $annualGrowthRatePct,
        ?float $startShareValue = null,
    ): float {
        if ($targetMonths <= 0) {
            throw new InvalidArgumentException('target_months must be > 0');
        }

        // Resolve starting share value for the upper-bound estimate.
        if ($startShareValue !== null) {
            $sv = (float) $startShareValue;
        } else {
            $account = $line->account()->first();
            $sv = $account ? (float) $account->shareValueAsOf(Carbon::today()->toDateString()) : 1.0;
        }
        if ($sv <= 0) {
            $sv = 1.0;
        }

        $outstanding = (float) $line->outstanding_shares;
        $lo = 0.01;
        $hi = max($outstanding * $sv * 10.0, 1.0);

        // Track best candidate that pays off in <= target months — fallback if exact match isn't found.
        $best = null;        // ['payment' => float, 'payoff_month' => int]

        for ($i = 0; $i < 60; $i++) {
            $mid = ($lo + $hi) / 2.0;
            $result = $this->simulate($line, $mid, $annualGrowthRatePct, $startShareValue);
            $payoff = $result->payoff_month;

            if ($payoff !== null && $payoff <= $targetMonths) {
                // Pays off at or before target — record as candidate, then try
                // a lower payment (smallest payment that still hits the target,
                // whether it lands exactly on or before it).
                if ($best === null || $mid < $best['payment']) {
                    $best = ['payment' => $mid, 'payoff_month' => $payoff];
                }
                $hi = $mid;
            } else {
                // Doesn't pay off in time (or capped) — must pay more.
                $lo = $mid;
            }

            if ($hi - $lo < 0.01) {
                break;
            }
        }

        if ($best !== null) {
            return round($best['payment'], 2);
        }

        // Bracket didn't yield a payoff-in-time — return the high bound as the best guess.
        return round($hi, 2);
    }

    /**
     * Solve for required monthly payment under all three scenarios.
     *
     * @param  array<string,float|null>|null $rateOverrides  Optional per-scenario
     *         annual-rate overrides; see {@see resolveRates()}.
     * @return array<string,array{payment: float, result: SimulationResult}>
     *   Keys: 'conservative', 'expected', 'aggressive'.
     */
    public function solveForPaymentAllScenarios(
        AccountCreditLine $line,
        int $targetMonths,
        ?array $rateOverrides = null
    ): array {
        if ($targetMonths <= 0) {
            throw new InvalidArgumentException('target_months must be > 0');
        }

        ['rates' => $rates, 'startShareValue' => $startShareValue] = $this->resolveScenarios($line, $rateOverrides);

        $out = [];
        foreach ($rates as $key => $rate) {
            $payment = $this->solveForPayment($line, $targetMonths, $rate, $startShareValue);
            $result = $this->simulate($line, $payment, $rate, $startShareValue);
            $out[$key] = ['payment' => $payment, 'result' => $result];
        }

        return $out;
    }
}
