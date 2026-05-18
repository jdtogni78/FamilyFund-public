<?php

namespace App\Services\CreditLine\Reporting;

use App\Models\AccountCreditLine;
use App\Models\CreditLineAdjustment;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use Carbon\Carbon;

/**
 * Builds the multi-generation payoff-trajectory dataset for a credit line.
 *
 * Returns a structured array suitable both for rendering a chart (via
 * QuickChartService) and for textual summaries. Implements UC-15 / UC-42 plan
 * baselines + actual + projection.
 *
 * Output shape:
 *   [
 *     'origination_date'     => 'Y-m-d' | null, // anchors the chart at (date, 0)
 *     'original_plan'        => [ ['date' => 'Y-m-d', 'cumulative_shares' => float], ... ],
 *     'historical_plans'     => [
 *         [
 *             'adjusted_at' => 'Y-m-d',
 *             'series'      => [ ['date','cumulative_shares'], ... ],
 *         ], ...
 *     ],
 *     'current_plan'         => [ ['date','cumulative_shares'], ... ],
 *     'actual_repayments'    => [ ['date','cumulative_shares'], ... ],
 *     'projected_payoff_date' => 'Y-m-d' | null,
 *     'planned_payoff_date'   => 'Y-m-d' | null,
 *     'variance_days'         => int | null, // negative = ahead of schedule
 *   ]
 */
class TrajectoryBuilder
{
    public function build(AccountCreditLine $line): array
    {
        $adjustments = CreditLineAdjustment::where('account_credit_line_id', $line->id)
            ->orderBy('adjusted_at')
            ->get();

        $allPayments = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        // Original generation: rows created at or before the first adjustment
        // (or all rows if no adjustment ever occurred).
        $firstAdjustedAt = $adjustments->isNotEmpty()
            ? Carbon::parse($adjustments->first()->adjusted_at)
            : null;

        // Use strict-less-than: rows created at the same instant as the
        // first adjustment belong to the new generation, not the original.
        // (Without this, readjust-time rows whose created_at == adjusted_at
        // would be double-counted in the original plan series.)
        $originalGen = $firstAdjustedAt
            ? $allPayments->filter(fn ($p) => $p->created_at && $p->created_at->lt($firstAdjustedAt))->values()
            : $allPayments->values();

        $originalPlan = $this->cumulativeFromPayments($originalGen, $line->principal_shares);

        // Historical plans: one per past adjustment. The "generation N" rows are
        // those created on or before the next adjustment (or now for the latest).
        $historicalPlans = [];
        $count = $adjustments->count();
        for ($i = 0; $i < $count; $i++) {
            $adj = $adjustments[$i];
            $genStart = Carbon::parse($adj->adjusted_at);
            $genEnd = ($i + 1 < $count)
                ? Carbon::parse($adjustments[$i + 1]->adjusted_at)
                : null;

            $genPayments = $allPayments->filter(function ($p) use ($genStart, $genEnd) {
                if (!$p->created_at) {
                    return false;
                }
                if ($p->created_at->lt($genStart)) {
                    return false;
                }
                if ($genEnd && $p->created_at->gte($genEnd)) {
                    return false;
                }
                return true;
            })->values();

            // Skip empty generations rather than render an empty line.
            if ($genPayments->isEmpty()) {
                continue;
            }

            // For historical plans (other than the latest), they are superseded;
            // we use the snapshot of `outstanding_shares_at_adjustment` as the
            // starting cumulative offset.
            $base = (float) ($line->principal_shares - $adj->outstanding_shares_at_adjustment);
            $historicalPlans[] = [
                'adjusted_at' => Carbon::parse($adj->adjusted_at)->format('Y-m-d'),
                'series'      => $this->cumulativeFromPayments($genPayments, $line->principal_shares, $base),
            ];
        }

        // Current plan = the *latest* generation's scheduled rows, plus paid ones
        // that belong to that generation. If no adjustments exist, this equals
        // the original plan.
        if ($count === 0) {
            $currentPlan = $originalPlan;
        } else {
            $currentPlan = !empty($historicalPlans)
                ? end($historicalPlans)['series']
                : $originalPlan;
        }

        // Actual repayments: cleared REP transactions, not reversed.
        $reps = TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_REPAY)
            ->where('status', TransactionExt::STATUS_CLEARED)
            ->where('reversed', false)
            ->orderBy('timestamp')
            ->get();

        $actual = [];
        $running = 0.0;
        foreach ($reps as $tx) {
            $running += (float) $tx->shares;
            $actual[] = [
                'date'              => Carbon::parse($tx->timestamp)->format('Y-m-d'),
                'cumulative_shares' => round($running, 4),
            ];
        }

        // Delinquency overlay: what the *current* schedule says should have
        // been repaid by today, plus the overdue backlog. The plan series
        // above is the static contractual baseline and never reshapes for
        // lateness — this is the series that makes "behind" visible, even
        // when nothing has been repaid yet (actual series empty).
        //
        // Base set = non-cancelled rows. With no readjustments this is the
        // whole schedule; after a readjust the superseded rows are cancelled,
        // so this stays aligned with the live schedule table.
        $today = Carbon::today();
        $expected = [];
        $running = 0.0;
        $overdueShares = 0.0;
        $overdueInstallments = 0;
        foreach ($allPayments as $p) {
            if ($p->status === CreditLinePayment::STATUS_CANCELLED) {
                continue;
            }
            $due = Carbon::parse($p->due_date);
            if ($due->gt($today)) {
                continue;
            }
            $running += (float) $p->shares_due;
            $expected[] = [
                'date'              => $due->format('Y-m-d'),
                'cumulative_shares' => round(min($running, (float) $line->principal_shares), 4),
            ];
            if ($p->status === CreditLinePayment::STATUS_LATE) {
                $overdueShares += (float) $p->shares_due;
                $overdueInstallments++;
            }
        }
        $overdueShares = round($overdueShares, 4);

        // Projected payoff: trailing-3-payments average run-rate.
        $projected = $this->projectedPayoffDate($reps, $line);

        // Planned payoff = last current_plan date.
        $planned = null;
        if (!empty($currentPlan)) {
            $planned = end($currentPlan)['date'];
        } elseif ($line->maturity_date) {
            $planned = Carbon::parse($line->maturity_date)->format('Y-m-d');
        }

        $variance = null;
        if ($projected && $planned) {
            $variance = Carbon::parse($planned)->diffInDays(Carbon::parse($projected), false);
        }

        return [
            'origination_date'      => $line->origination_date
                ? Carbon::parse($line->origination_date)->format('Y-m-d')
                : null,
            'original_plan'         => $originalPlan,
            'historical_plans'      => $historicalPlans,
            'current_plan'          => $currentPlan,
            'actual_repayments'     => $actual,
            'expected_to_date'      => $expected,
            'overdue_shares'        => $overdueShares,
            'overdue_installments'  => $overdueInstallments,
            'as_of'                 => $today->format('Y-m-d'),
            'projected_payoff_date' => $projected,
            'planned_payoff_date'   => $planned,
            'variance_days'         => $variance,
        ];
    }

    /**
     * Convert a collection of CreditLinePayment rows into cumulative-paid points.
     * Starts at $base (default 0) and adds each row's shares_due.
     *
     * @param  iterable<CreditLinePayment> $payments
     */
    private function cumulativeFromPayments($payments, float $principal, float $base = 0.0): array
    {
        $series = [];
        $running = $base;
        foreach ($payments as $p) {
            $running += (float) $p->shares_due;
            $series[] = [
                'date'              => Carbon::parse($p->due_date)->format('Y-m-d'),
                'cumulative_shares' => round(min($running, $principal), 4),
            ];
        }
        return $series;
    }

    /**
     * Trailing-3-payments average run-rate → extrapolate remaining outstanding.
     * Returns null if fewer than 2 cleared REP transactions exist.
     */
    private function projectedPayoffDate($reps, AccountCreditLine $line): ?string
    {
        if ($reps->count() < 2) {
            return null;
        }

        $tail = $reps->slice(-3)->values();
        $first = Carbon::parse($tail->first()->timestamp);
        $last  = Carbon::parse($tail->last()->timestamp);
        $sharesPaid = (float) $tail->sum('shares');
        $daysSpan = max(1, $first->diffInDays($last));
        if ($sharesPaid <= 0) {
            return null;
        }
        $sharesPerDay = $sharesPaid / $daysSpan;

        $remaining = (float) $line->outstanding_shares;
        if ($remaining <= 0) {
            return Carbon::now()->format('Y-m-d');
        }

        $daysToPayoff = (int) ceil($remaining / $sharesPerDay);
        return $last->copy()->addDays($daysToPayoff)->format('Y-m-d');
    }
}
