<?php

namespace App\Services;

use App\Models\AccountExt;
use App\Models\GoalExt;
use Carbon\Carbon;

/**
 * Computes per-goal progress for an account.
 *
 * `current` is the **net** view (OWN − BOR via AccountExt::valueAsOf) —
 * borrowed shares aren't held, so net is the one truthful figure for goal
 * progress. `current_gross` is exposed for legacy callers that explicitly
 * need the pre-borrowing OWN total; views should keep reading `current`.
 */
class GoalCalculationService
{
    public function progressFor(AccountExt $account, GoalExt $goal, Carbon|string $asOf): array
    {
        $asOfDt = $asOf instanceof Carbon ? $asOf : new Carbon($asOf);
        $asOfStr = $asOfDt->toDateString();

        $shareValue = $account->shareValueAsOf($asOfStr);
        $balances   = $account->allSharesAsOf($asOfStr);
        $ownShares  = isset($balances['OWN']) ? (float) $balances['OWN']->shares : 0.0;
        $borShares  = isset($balances['BOR']) ? (float) $balances['BOR']->shares : 0.0;

        $netValue      = $shareValue * ($ownShares - $borShares);
        $grossValue    = $shareValue * $ownShares;
        $borrowedValue = $shareValue * $borShares;

        $startValue  = (float) $account->valueAsOf($goal->start_dt);
        $targetValue = $this->targetValue($goal);

        $totalDays   = max(1, $goal->start_dt->diffInDays($goal->end_dt));
        $currentDays = $goal->start_dt->diffInDays(Carbon::now());

        $valuePerDay   = max(0.0, ($targetValue - $startValue)) / $totalDays;
        $expectedValue = $startValue + ($valuePerDay * $currentDays);

        $pct = (float) $goal->target_pct;

        return [
            'period'          => [$currentDays, $totalDays, ($currentDays / $totalDays) * 100.0],
            'start_value'     => $this->bucket($startValue,    $startValue, $targetValue, $pct),
            'current'         => $this->bucket($netValue,      $startValue, $targetValue, $pct),
            'current_gross'   => $this->bucket($grossValue,    $startValue, $targetValue, $pct),
            'expected'        => $this->bucket($expectedValue, $startValue, $targetValue, $pct),
            'borrowed_value'  => $borrowedValue,
            'borrowed_shares' => $borShares,
        ];
    }

    private function targetValue(GoalExt $goal): float
    {
        if ($goal->target_type === GoalExt::TARGET_TYPE_TOTAL) {
            return (float) $goal->target_amount;
        }
        if ($goal->target_type === GoalExt::TARGET_TYPE_4PCT) {
            return $goal->target_pct > 0
                ? (float) $goal->target_amount / (float) $goal->target_pct
                : 0.0;
        }
        return 0.0;
    }

    private function bucket(float $value, float $start, float $target, float $targetPct): array
    {
        $denom = $target - $start;
        $completed = $denom > 0
            ? min(100.0, (($value - $start) / $denom) * 100.0)
            : 0.0;

        return [
            'value'             => $value,
            'value_4pct'        => $value * $targetPct,
            'final_value'       => $target,
            'final_value_4pct'  => $target * $targetPct,
            'completed_pct'     => $completed,
        ];
    }
}
