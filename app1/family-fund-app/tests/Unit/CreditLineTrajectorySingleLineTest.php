<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Regression test for the trajectory chart collapsing to ONE plan line and
 * ONE actual line.
 *
 * Previously the partial drew every TrajectoryBuilder series as its own
 * line — "Original plan", a "Plan after <date>" per adjustment, "Current
 * plan", a red expected-to-date "Actual" overlay, and "Actual repayments"
 * — i.e. 4+ parallel lines for what is conceptually one evolving plan vs.
 * one actual. The fix splices the original schedule (until the first
 * adjustment) with each generation's segment into a single "Scheduled
 * plan" line and keeps only "Actual repayments".
 */
class CreditLineTrajectorySingleLineTest extends TestCase
{
    /**
     * Render the partial and return the decoded Chart.js datasets.
     *
     * @return array<int,array<string,mixed>>
     */
    private function datasets(array $trajectory): array
    {
        $html = view('account_credit_lines._trajectory_chart', [
            'trajectory' => $trajectory,
            'chartUrl'   => null,
        ])->render();

        $this->assertMatchesRegularExpression(
            '#/chart\?c=#',
            $html,
            'Expected an inline QuickChart <img src> with an encoded config.'
        );
        preg_match('#/chart\?c=([^"&]+)#', $html, $m);
        $config = json_decode(urldecode($m[1]), true);

        return $config['data']['datasets'];
    }

    /** @return string[] */
    private function labels(array $datasets): array
    {
        return array_column($datasets, 'label');
    }

    /**
     * The case that used to render 4+ lines: an original plan, a historical
     * generation from an adjustment, a current plan, an expected-to-date
     * overlay, and actual repayments. Must collapse to exactly two lines.
     */
    public function test_multi_generation_renders_exactly_one_plan_and_one_actual(): void
    {
        $datasets = $this->datasets([
            'original_plan' => [
                ['date' => '2026-01-01', 'cumulative_shares' => 0],
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-01', 'cumulative_shares' => 40],
            ],
            'historical_plans' => [
                ['adjusted_at' => '2026-02-15', 'series' => [
                    ['date' => '2026-02-15', 'cumulative_shares' => 30],
                    ['date' => '2026-03-15', 'cumulative_shares' => 55],
                    ['date' => '2026-04-15', 'cumulative_shares' => 80],
                ]],
            ],
            'current_plan' => [
                ['date' => '2026-02-15', 'cumulative_shares' => 30],
                ['date' => '2026-03-15', 'cumulative_shares' => 55],
                ['date' => '2026-04-15', 'cumulative_shares' => 80],
            ],
            'actual_repayments' => [
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-10', 'cumulative_shares' => 35],
            ],
            'expected_to_date' => [
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-01', 'cumulative_shares' => 40],
            ],
            'overdue_shares' => 5,
            'overdue_installments' => 1,
            'as_of' => '2026-03-20',
            'projected_payoff_date' => null,
            'planned_payoff_date' => '2026-04-15',
            'variance_days' => null,
        ]);

        $this->assertSame(
            ['Scheduled plan', 'Actual repayments'],
            $this->labels($datasets),
            'Multi-generation trajectory must render exactly one plan line and one actual line.'
        );
    }

    /**
     * The legacy per-series labels must be gone — in particular a dataset
     * labelled exactly "Actual" (the old red expected-to-date overlay,
     * distinct from "Actual repayments").
     */
    public function test_legacy_per_series_lines_are_not_emitted(): void
    {
        $labels = $this->labels($this->datasets([
            'original_plan' => [
                ['date' => '2026-01-01', 'cumulative_shares' => 0],
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
            ],
            'historical_plans' => [
                ['adjusted_at' => '2026-01-20', 'series' => [
                    ['date' => '2026-01-20', 'cumulative_shares' => 15],
                    ['date' => '2026-02-20', 'cumulative_shares' => 40],
                ]],
            ],
            'current_plan' => [
                ['date' => '2026-01-20', 'cumulative_shares' => 15],
                ['date' => '2026-02-20', 'cumulative_shares' => 40],
            ],
            'actual_repayments' => [['date' => '2026-02-01', 'cumulative_shares' => 20]],
            'expected_to_date' => [['date' => '2026-02-01', 'cumulative_shares' => 20]],
            'overdue_shares' => 0,
            'overdue_installments' => 0,
            'as_of' => '2026-02-05',
            'projected_payoff_date' => null,
            'planned_payoff_date' => '2026-02-20',
            'variance_days' => null,
        ]));

        $this->assertNotContains('Original plan', $labels);
        $this->assertNotContains('Current plan', $labels);
        $this->assertNotContains('Plan after 2026-01-20', $labels);
        $this->assertNotContains('Actual', $labels, 'The red expected-to-date "Actual" overlay must be gone.');
    }

    /**
     * The single plan line is the builder's pre-spliced effective_plan —
     * the schedule actually in force over time, one continuous monotonic
     * series keyed on due_date that switches generations at each
     * adjustment's effective_date and carries its running cumulative across
     * the boundary. The blade just draws it (anchored at origination and
     * carried forward over the merged x-axis); it must NOT re-derive a plan
     * by splicing original_plan + historical_plans on adjusted_at — the old
     * behaviour that mixed two time axes and two cumulative baselines,
     * producing the dip-then-climb / sawtooth.
     */
    public function test_scheduled_plan_uses_continuous_effective_plan_not_adjusted_at_splice(): void
    {
        $datasets = $this->datasets([
            'origination_date' => '2026-01-01',
            // The builder already spliced this: original rows (20, 40) then
            // the post-readjust generation continuing from 40 (+10, +10) —
            // monotonic, no reset to principal − outstanding.
            'effective_plan' => [
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-01', 'cumulative_shares' => 40],
                ['date' => '2026-05-01', 'cumulative_shares' => 50],
                ['date' => '2026-06-01', 'cumulative_shares' => 60],
            ],
            // Legacy per-generation series with the mismatched baseline that
            // the OLD blade splice would have drawn as 40 → 30 (a dip). The
            // blade must ignore these in favour of effective_plan.
            'original_plan' => [
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-01', 'cumulative_shares' => 40],
                ['date' => '2026-04-01', 'cumulative_shares' => 60],
            ],
            'historical_plans' => [
                ['adjusted_at' => '2026-03-20', 'series' => [
                    ['date' => '2026-05-01', 'cumulative_shares' => 30],
                    ['date' => '2026-06-01', 'cumulative_shares' => 40],
                ]],
            ],
            'current_plan' => [
                ['date' => '2026-05-01', 'cumulative_shares' => 30],
                ['date' => '2026-06-01', 'cumulative_shares' => 40],
            ],
            'actual_repayments' => [
                ['date' => '2026-02-10', 'cumulative_shares' => 20],
            ],
            'expected_to_date' => [],
            'overdue_shares' => 0,
            'overdue_installments' => 0,
            'as_of' => '2026-06-10',
            'projected_payoff_date' => null,
            'planned_payoff_date' => '2026-06-01',
            'variance_days' => null,
        ]);

        $byLabel = array_column($datasets, 'data', 'label');

        // x-axis = origination + sorted union of plan + actual dates:
        // 2026-01-01, 02-01, 02-10, 03-01, 05-01, 06-01
        // Plan: anchored at origination 0, then the continuous effective_plan
        // (20, 40, 50, 60) carried forward across the gaps.
        $plan = $byLabel['Scheduled plan'];
        $this->assertSame([0, 20, 20, 40, 50, 60], $plan);

        // Monotonic non-decreasing — the readjust must not introduce a dip.
        $prev = -INF;
        foreach ($plan as $v) {
            $this->assertGreaterThanOrEqual($prev, $v, 'plan line must never dip');
            $prev = $v;
        }
        // The legacy adjusted_at-spliced baseline (40 → 30) must NOT appear.
        $this->assertNotContains(30, $plan);

        // Actual repayments anchored at origination 0, then 20 at 02-10, then
        // stop — the 03-01 / 05-01 / 06-01 plan dates carry no flat tail
        // (the actual line ends at the last real payment).
        $this->assertSame([0, 0, 20, null, null, null], $byLabel['Actual repayments']);
    }

    /**
     * Both lines must be anchored at (origination_date, 0): at origination
     * nothing has been repaid, so the chart should start from the origin
     * rather than jumping in at the first installment / first repayment.
     */
    public function test_lines_are_anchored_at_origination_zero_point(): void
    {
        $datasets = $this->datasets([
            'origination_date' => '2026-01-01',
            'original_plan' => [
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-01', 'cumulative_shares' => 40],
            ],
            'historical_plans' => [],
            'current_plan' => [
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-01', 'cumulative_shares' => 40],
            ],
            'actual_repayments' => [
                ['date' => '2026-02-15', 'cumulative_shares' => 15],
            ],
            'expected_to_date' => [],
            'overdue_shares' => 0,
            'overdue_installments' => 0,
            'as_of' => '2026-03-05',
            'projected_payoff_date' => null,
            'planned_payoff_date' => '2026-03-01',
            'variance_days' => null,
        ]);

        $byLabel = array_column($datasets, 'data', 'label');

        // x-axis = origination + sorted union of plan + actual dates:
        // 2026-01-01, 02-01, 02-15, 03-01
        // Plan starts at the origination 0, then 20, carried to 02-15, then 40.
        $this->assertSame([0, 20, 20, 40], $byLabel['Scheduled plan']);
        // Actual anchored at the origination 0, flat until the repayment,
        // then stops — the 2026-03-01 plan date carries no flat tail.
        $this->assertSame([0, 0, 15, null], $byLabel['Actual repayments']);
    }

    /**
     * Without an origination_date in the trajectory (legacy callers) the
     * series must be left untouched — no synthetic zero point.
     */
    public function test_no_origination_date_leaves_series_unanchored(): void
    {
        $datasets = $this->datasets([
            'original_plan' => [
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-01', 'cumulative_shares' => 40],
            ],
            'historical_plans' => [],
            'current_plan' => [
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-01', 'cumulative_shares' => 40],
            ],
            'actual_repayments' => [],
            'expected_to_date' => [],
            'overdue_shares' => 0,
            'overdue_installments' => 0,
            'as_of' => '2026-03-05',
            'projected_payoff_date' => null,
            'planned_payoff_date' => '2026-03-01',
            'variance_days' => null,
        ]);

        $byLabel = array_column($datasets, 'data', 'label');
        $this->assertSame([20, 40], $byLabel['Scheduled plan']);
    }

    /**
     * The actual line must STOP at the last real payment, not run flat to
     * plan maturity. The plan dates that extend past the final repayment
     * carry a null (line ends) rather than the carried-forward last value.
     */
    public function test_actual_line_stops_at_last_actual_payment(): void
    {
        $datasets = $this->datasets([
            'original_plan' => [
                ['date' => '2026-01-01', 'cumulative_shares' => 0],
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-01', 'cumulative_shares' => 40],
                ['date' => '2026-04-01', 'cumulative_shares' => 60],
            ],
            'historical_plans' => [],
            'current_plan' => [
                ['date' => '2026-01-01', 'cumulative_shares' => 0],
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ['date' => '2026-03-01', 'cumulative_shares' => 40],
                ['date' => '2026-04-01', 'cumulative_shares' => 60],
            ],
            'actual_repayments' => [
                ['date' => '2026-02-01', 'cumulative_shares' => 18],
                ['date' => '2026-03-01', 'cumulative_shares' => 33],
            ],
            'expected_to_date' => [],
            'overdue_shares' => 0,
            'overdue_installments' => 0,
            'as_of' => '2026-03-10',
            'projected_payoff_date' => null,
            'planned_payoff_date' => '2026-04-01',
            'variance_days' => null,
        ]);

        $byLabel = array_column($datasets, 'data', 'label');

        // x-axis = sorted union: 2026-01-01, 02-01, 03-01, 04-01.
        // Plan runs its full length; actual stops at the last payment
        // (2026-03-01), so 2026-04-01 is null — no flat tail to maturity.
        $this->assertSame([0, 20, 40, 60], $byLabel['Scheduled plan']);
        $this->assertSame([null, 18, 33, null], $byLabel['Actual repayments']);
    }

    /**
     * With no adjustments and no repayments yet there is a single plan line
     * and nothing else — never the old red overlay.
     */
    public function test_no_adjustment_no_actual_renders_only_the_plan_line(): void
    {
        $datasets = $this->datasets([
            'original_plan' => [
                ['date' => '2026-01-01', 'cumulative_shares' => 0],
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
            ],
            'historical_plans' => [],
            'current_plan' => [
                ['date' => '2026-01-01', 'cumulative_shares' => 0],
                ['date' => '2026-02-01', 'cumulative_shares' => 20],
            ],
            'actual_repayments' => [],
            'expected_to_date' => [],
            'overdue_shares' => 0,
            'overdue_installments' => 0,
            'as_of' => '2026-01-15',
            'projected_payoff_date' => null,
            'planned_payoff_date' => '2026-02-01',
            'variance_days' => null,
        ]);

        $this->assertSame(['Scheduled plan'], $this->labels($datasets));
    }
}
