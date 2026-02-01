<?php

namespace App\Models;

use App\Models\Fund;
use App\Repositories\AccountRepository;
use App\Repositories\AccountBalanceRepository;
use App\Models\Utils;
use App\Repositories\FundRepository;
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
        return $this->four_pct_yearly_expenses !== null && $this->four_pct_yearly_expenses > 0;
    }

    /**
     * Get the withdrawal rate (default 4%).
     */
    public function getWithdrawalRate(): float
    {
        return (float) ($this->withdrawal_rate ?? 4.00);
    }

    /**
     * Get the target value for the withdrawal goal (expenses / withdrawal_rate * 100).
     */
    public function withdrawalTargetValue(): float
    {
        if (!$this->hasWithdrawalGoal()) {
            return 0;
        }
        $rate = $this->getWithdrawalRate();
        if ($rate <= 0) {
            return 0;
        }
        return (float) $this->four_pct_yearly_expenses / ($rate / 100);
    }

    /**
     * Get the net worth percentage to use (default 100%).
     */
    public function withdrawalNetWorthPct(): float
    {
        return (float) ($this->four_pct_net_worth_pct ?? 100.00);
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

        $targetValue = $this->withdrawalTargetValue();
        $adjustedValue = $this->withdrawalAdjustedValue($asOf);
        $currentYield = $this->withdrawalCurrentYield($asOf);
        $targetYield = (float) $this->four_pct_yearly_expenses;
        $netWorthPct = $this->withdrawalNetWorthPct();
        $withdrawalRate = $this->getWithdrawalRate();

        // Progress percentage (capped at 100%)
        $progressPct = $targetValue > 0 ? min(100, ($adjustedValue / $targetValue) * 100) : 0;

        return [
            'yearly_expenses' => $targetYield,
            'target_value' => $targetValue,
            'net_worth_pct' => $netWorthPct,
            'adjusted_value' => $adjustedValue,
            'current_yield' => $currentYield,
            'progress_pct' => $progressPct,
            'is_reached' => $adjustedValue >= $targetValue,
            'withdrawal_rate' => $withdrawalRate,
        ];
    }
}
