<?php

namespace App\Models;

use App\Models\Fund;
use App\Repositories\AccountRepository;
use App\Repositories\AccountBalanceRepository;
use App\Models\Utils;
use App\Repositories\FundRepository;
use Carbon\Carbon;
/**
 * Class FundExt
 * @package App\Models
 */
class FundExt extends Fund
{
    public static function fundMap()
    {
        $fundRepo = \App::make(FundRepository::class);
        $recs = $fundRepo->all([], null, null, ['id', 'name'])->toArray();
        $out = [null => 'Please Select Fund'];
        foreach ($recs as $row) {
            $out[$row['id']] = $row['name'];
        }
        return $out;
    }

    /**
     * Get the primary portfolio for this fund (first one).
     * For backward compatibility. Use portfolios() for all portfolios.
     */
    public function portfolio()
    {
        return $this->portfolios()->first();
    }

    /**
     * Get the fund account (the account with null user_id).
     * Returns null if not found instead of throwing.
     */
    public function account() : ?AccountExt
    {
        return $this->fundAccount();
    }

    /**
     * @return money
     **/

    public function sharesAsOf($now)
    {
        $account = $this->account();
        if (!$account) {
            return 0;
        }
        return $account->sharesAsOf($now);
    }

    /**
     * Get the total value of the fund as of a given date.
     * Sums values from ALL portfolios associated with this fund.
     */
    public function valueAsOf($now, $verbose=false)
    {
        $totalValue = 0;
        /** @var PortfolioExt $portfolio */
        foreach ($this->portfolios()->get() as $portfolio) {
            $totalValue += $portfolio->valueAsOf($now, $verbose);
        }
        return $totalValue;
    }

    public function shareValueAsOf($now)
    {
        $value = $this->valueAsOf($now);
        $shares = $this->sharesAsOf($now);
        if ($shares == 0) return 0;
        return $value / $shares;
    }

    public function allocatedShares($now, $inverse=false) {
        $accountRepo = \App::make(AccountRepository::class);
        $query = $accountRepo->makeModel()->newQuery();
        $query->where('fund_id', $this->id);
        $accounts = $query->get(['*']);

        $used = 0;
        $total = 0;
        foreach ($accounts as $account) {
            $balance = $account->sharesAsOf($now);
            if ($account->user_id) {
                $used += $balance;
            } else {
                $total = $balance;
            }
        }

        return $inverse? $total-$used : $used;
    }

    public function unallocatedShares($now) {
        return $this->allocatedShares($now, true);
    }

    /**
     * Calculate period performance aggregated across all portfolios.
     * Returns weighted average based on portfolio values.
     */
    public function periodPerformance($from, $to)
    {
        $portfolios = $this->portfolios()->get();
        if ($portfolios->isEmpty()) {
            return 0;
        }

        // For single portfolio, return directly
        if ($portfolios->count() === 1) {
            return $portfolios->first()->periodPerformance($from, $to);
        }

        // For multiple portfolios, calculate weighted average performance
        $totalStartValue = 0;
        $totalEndValue = 0;
        foreach ($portfolios as $portfolio) {
            $startValue = $portfolio->valueAsOf($from);
            $endValue = $portfolio->valueAsOf($to);
            $totalStartValue += $startValue;
            $totalEndValue += $endValue;
        }

        if ($totalStartValue == 0) {
            return 0;
        }

        return ($totalEndValue - $totalStartValue) / $totalStartValue;
    }

    /**
     * Calculate yearly performance aggregated across all portfolios.
     */
    public function yearlyPerformance($year)
    {
        $portfolios = $this->portfolios()->get();
        if ($portfolios->isEmpty()) {
            return 0;
        }

        // For single portfolio, return directly
        if ($portfolios->count() === 1) {
            return $portfolios->first()->yearlyPerformance($year);
        }

        // For multiple portfolios, calculate weighted average performance
        $from = "$year-01-01";
        $to = "$year-12-31";
        return $this->periodPerformance($from, $to);
    }

    public function accountBalancesAsOf($asOf)
    {
        $accountBalanceRepo = \App::make(AccountBalanceRepository::class);
        $query = $accountBalanceRepo->makeModel()->newQuery();
        $query->whereDate('start_dt', '<=', $asOf);
        $query->whereDate('end_dt', '>', $asOf);

        $query->leftJoin('accounts', 'accounts.id', '=', 'account_balances.account_id');
        $query->where('fund_id', '=', $this->id);

        $accountBalances = $query->get(['*']);
        return $accountBalances;
    }

    public function fundAccount()
    {
        return $this->accounts()->where('user_id', null)->first();
    }

    public function findOldestTransaction() {
        $account = $this->account();
        if (!$account) {
            return null;
        }
        $trans = $account->transactions()->get();
        $tran = $trans->sortBy('timestamp')->first();
        return $tran;
    }

    /**
     * Check if fund has a withdrawal rule goal configured.
     */
    public function hasWithdrawalGoal(): bool
    {
        return $this->withdrawal_yearly_expenses !== null && $this->withdrawal_yearly_expenses > 0;
    }

    /**
     * Get the withdrawal rate (default 4%).
     */
    public function getWithdrawalRate(): float
    {
        return (float) ($this->withdrawal_rate ?? 4.00);
    }

    /**
     * Get the expected growth rate (default 7%).
     */
    public function getExpectedGrowthRate(): float
    {
        return (float) ($this->expected_growth_rate ?? 7.00);
    }

    /**
     * Get the independence mode ('perpetual' or 'countdown').
     * Defaults to 'perpetual' if not set.
     */
    public function getIndependenceMode(): string
    {
        return $this->independence_mode ?? 'perpetual';
    }

    /**
     * Get the independence target date for countdown mode.
     * Returns null if not in countdown mode or date not set.
     */
    public function getIndependenceTargetDate(): ?Carbon
    {
        if ($this->getIndependenceMode() !== 'countdown') {
            return null;
        }
        return $this->independence_target_date;
    }

    /**
     * Get the years remaining until independence target date.
     * Returns null if not in countdown mode or no target date set.
     *
     * @param string $asOf Current as-of date
     * @return float|null Years remaining (can be fractional), or null if not applicable
     */
    public function getYearsRemaining(string $asOf): ?float
    {
        $targetDate = $this->getIndependenceTargetDate();
        if (!$targetDate) {
            return null;
        }

        $currentDate = Carbon::parse($asOf);
        $diffInDays = $currentDate->diffInDays($targetDate, false);

        // Return fractional years (using 365.25 for accuracy)
        return $diffInDays / 365.25;
    }

    /**
     * Calculate the target value using present value of annuity formula for countdown mode.
     * Formula: Required = yearly_expenses × [(1 - (1 + growth_rate)^(-years)) / growth_rate]
     *
     * @param string $asOf Current as-of date
     * @return float Target value needed, or 0 if not applicable
     */
    public function calculateCountdownTargetValue(string $asOf): float
    {
        if (!$this->hasWithdrawalGoal()) {
            return 0;
        }

        $yearsRemaining = $this->getYearsRemaining($asOf);
        if ($yearsRemaining === null || $yearsRemaining <= 0) {
            return 0;
        }

        $yearlyExpenses = (float) $this->withdrawal_yearly_expenses;
        $growthRate = $this->getExpectedGrowthRate() / 100;

        if ($growthRate <= 0) {
            // Without growth, need simple years × expenses
            return $yearlyExpenses * $yearsRemaining;
        }

        // PV of annuity formula: PMT × [(1 - (1 + r)^(-n)) / r]
        $pvFactor = (1 - pow(1 + $growthRate, -$yearsRemaining)) / $growthRate;
        return $yearlyExpenses * $pvFactor;
    }

    /**
     * Get the countdown mode funding percentage.
     * Compares current adjusted value to countdown target value.
     *
     * @param string $asOf Current as-of date
     * @return float Funding percentage (0-100+), or 0 if not applicable
     */
    public function getCountdownFundingPct(string $asOf): float
    {
        $targetValue = $this->calculateCountdownTargetValue($asOf);
        if ($targetValue <= 0) {
            return 0;
        }

        $adjustedValue = $this->withdrawalAdjustedValue($asOf);
        return ($adjustedValue / $targetValue) * 100;
    }

    /**
     * Get the target value for the withdrawal goal.
     * In perpetual mode: expenses / withdrawal_rate * 100
     * In countdown mode: uses PV of annuity formula
     *
     * @param string|null $asOf Required for countdown mode calculations
     * @return float Target value needed
     */
    public function withdrawalTargetValue(?string $asOf = null): float
    {
        if (!$this->hasWithdrawalGoal()) {
            return 0;
        }

        // In countdown mode, use PV of annuity calculation
        if ($this->getIndependenceMode() === 'countdown' && $asOf !== null) {
            return $this->calculateCountdownTargetValue($asOf);
        }

        // Perpetual mode: expenses / withdrawal_rate * 100
        $rate = $this->getWithdrawalRate();
        if ($rate <= 0) {
            return 0;
        }
        return (float) $this->withdrawal_yearly_expenses / ($rate / 100);
    }

    /**
     * Get the net worth percentage to use (default 100%).
     */
    public function withdrawalNetWorthPct(): float
    {
        return (float) ($this->withdrawal_net_worth_pct ?? 100.00);
    }

    /**
     * Get the adjusted fund value based on net worth percentage.
     */
    public function withdrawalAdjustedValue($asOf): float
    {
        $fundValue = $this->valueAsOf($asOf);
        return $fundValue * ($this->withdrawalNetWorthPct() / 100);
    }

    /**
     * Get the current yield from adjusted value at the configured withdrawal rate.
     */
    public function withdrawalCurrentYield($asOf): float
    {
        return $this->withdrawalAdjustedValue($asOf) * ($this->getWithdrawalRate() / 100);
    }

    /**
     * Get complete withdrawal goal progress data.
     * Reuses pattern from AccountTrait::getGoalPct()
     */
    public function withdrawalProgress($asOf): array
    {
        if (!$this->hasWithdrawalGoal()) {
            return [];
        }

        $targetValue = $this->withdrawalTargetValue($asOf);
        $adjustedValue = $this->withdrawalAdjustedValue($asOf);
        $currentYield = $this->withdrawalCurrentYield($asOf);
        $targetYield = (float) $this->withdrawal_yearly_expenses;
        $netWorthPct = $this->withdrawalNetWorthPct();
        $withdrawalRate = $this->getWithdrawalRate();
        $independenceMode = $this->getIndependenceMode();

        // Progress percentage (capped at 100%)
        $progressPct = $targetValue > 0 ? min(100, ($adjustedValue / $targetValue) * 100) : 0;

        $result = [
            'yearly_expenses' => $targetYield,
            'target_value' => $targetValue,
            'net_worth_pct' => $netWorthPct,
            'adjusted_value' => $adjustedValue,
            'current_yield' => $currentYield,
            'progress_pct' => $progressPct,
            'is_reached' => $adjustedValue >= $targetValue,
            'withdrawal_rate' => $withdrawalRate,
            'expected_growth_rate' => $this->getExpectedGrowthRate(),
            'independence_mode' => $independenceMode,
        ];

        // Add countdown-specific fields
        if ($independenceMode === 'countdown') {
            $result['independence_target_date'] = $this->getIndependenceTargetDate()?->format('Y-m-d');
            $result['years_remaining'] = $this->getYearsRemaining($asOf);
        }

        return $result;
    }

    /**
     * Calculate years to reach target accounting for ongoing withdrawals.
     * Formula: net_rate = growth_rate - withdrawal_rate
     *          years = log(target / current) / log(1 + net_rate)
     *
     * @param string $asOf Current as-of date
     * @return array|null Target reach projection data, or null if not calculable
     */
    public function calculateTargetReachWithWithdrawals(string $asOf): ?array
    {
        if (!$this->hasWithdrawalGoal()) {
            return null;
        }

        $targetValue = $this->withdrawalTargetValue();
        $currentValue = $this->withdrawalAdjustedValue($asOf);
        $growthRate = $this->getExpectedGrowthRate() / 100;
        $withdrawalRate = $this->getWithdrawalRate() / 100;
        $netRate = $growthRate - $withdrawalRate;

        // Already reached
        if ($currentValue >= $targetValue) {
            return [
                'reachable' => true,
                'already_reached' => true,
                'expected_growth_rate' => $this->getExpectedGrowthRate(),
                'withdrawal_rate' => $this->getWithdrawalRate(),
                'net_growth_rate' => $netRate * 100,
            ];
        }

        // Cannot reach if withdrawals >= growth
        if ($netRate <= 0) {
            return [
                'reachable' => false,
                'reason' => 'withdrawals_exceed_growth',
                'expected_growth_rate' => $this->getExpectedGrowthRate(),
                'withdrawal_rate' => $this->getWithdrawalRate(),
                'net_growth_rate' => $netRate * 100,
            ];
        }

        // Cannot calculate if no current value
        if ($currentValue <= 0) {
            return [
                'reachable' => false,
                'reason' => 'no_value',
                'expected_growth_rate' => $this->getExpectedGrowthRate(),
                'withdrawal_rate' => $this->getWithdrawalRate(),
                'net_growth_rate' => $netRate * 100,
            ];
        }

        // Formula: years = log(target / current) / log(1 + net_rate)
        $yearsFromNow = log($targetValue / $currentValue) / log(1 + $netRate);

        // If more than 50 years away, mark as distant
        if ($yearsFromNow > 50) {
            return [
                'reachable' => true,
                'distant' => true,
                'years_from_now' => round($yearsFromNow, 1),
                'expected_growth_rate' => $this->getExpectedGrowthRate(),
                'withdrawal_rate' => $this->getWithdrawalRate(),
                'net_growth_rate' => $netRate * 100,
            ];
        }

        $estimatedTimestamp = strtotime($asOf) + ($yearsFromNow * 365 * 24 * 3600);
        $estimatedDate = date('Y-m-d', (int) $estimatedTimestamp);
        $estimatedDateFormatted = date('M Y', (int) $estimatedTimestamp);

        return [
            'reachable' => true,
            'estimated_date' => $estimatedDate,
            'estimated_date_formatted' => $estimatedDateFormatted,
            'years_from_now' => round($yearsFromNow, 1),
            'expected_growth_rate' => $this->getExpectedGrowthRate(),
            'withdrawal_rate' => $this->getWithdrawalRate(),
            'net_growth_rate' => $netRate * 100,
        ];
    }

    /**
     * Calculate years to reach target using expected growth rate.
     * Formula: years = log(target / current) / log(1 + growth_rate)
     *
     * @param string $asOf Current as-of date
     * @return array|null Target reach projection data, or null if already reached or not calculable
     */
    public function calculateTargetReachWithGrowthRate(string $asOf): ?array
    {
        if (!$this->hasWithdrawalGoal()) {
            return null;
        }

        $targetValue = $this->withdrawalTargetValue();
        $currentValue = $this->withdrawalAdjustedValue($asOf);
        $growthRate = $this->getExpectedGrowthRate() / 100;

        // Already reached
        if ($currentValue >= $targetValue) {
            return [
                'reachable' => true,
                'already_reached' => true,
                'expected_growth_rate' => $this->getExpectedGrowthRate(),
            ];
        }

        // Cannot calculate if no growth or no current value
        if ($growthRate <= 0 || $currentValue <= 0) {
            return [
                'reachable' => false,
                'reason' => $growthRate <= 0 ? 'no_growth' : 'no_value',
                'expected_growth_rate' => $this->getExpectedGrowthRate(),
            ];
        }

        // Formula: years = log(target / current) / log(1 + growth_rate)
        $yearsFromNow = log($targetValue / $currentValue) / log(1 + $growthRate);

        // If more than 50 years away, mark as distant
        if ($yearsFromNow > 50) {
            return [
                'reachable' => true,
                'distant' => true,
                'years_from_now' => round($yearsFromNow, 1),
                'expected_growth_rate' => $this->getExpectedGrowthRate(),
            ];
        }

        $estimatedTimestamp = strtotime($asOf) + ($yearsFromNow * 365 * 24 * 3600);
        $estimatedDate = date('Y-m-d', (int) $estimatedTimestamp);
        $estimatedDateFormatted = date('M Y', (int) $estimatedTimestamp);

        return [
            'reachable' => true,
            'estimated_date' => $estimatedDate,
            'estimated_date_formatted' => $estimatedDateFormatted,
            'years_from_now' => round($yearsFromNow, 1),
            'expected_growth_rate' => $this->getExpectedGrowthRate(),
        ];
    }
}
