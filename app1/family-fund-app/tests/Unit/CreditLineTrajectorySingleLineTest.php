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
     * The single plan line must be the schedule actually in force over time:
     * the original plan until the first adjustment, then the adjusted
     * generation's schedule — not the original running its full length.
     */
    public function test_scheduled_plan_splices_original_then_adjusted_generation(): void
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
            'expected_to_date' => [],
            'overdue_shares' => 0,
            'overdue_installments' => 0,
            'as_of' => '2026-04-20',
            'projected_payoff_date' => null,
            'planned_payoff_date' => '2026-04-15',
            'variance_days' => null,
        ]);

        $byLabel = array_column($datasets, 'data', 'label');

        // x-axis = sorted union of plan + actual dates:
        // 2026-01-01, 02-01, 02-15, 03-10, 03-15, 04-15
        // Plan: original until the 2026-02-15 adjustment (0, 20), then the
        // adjusted generation (30, …, 55, 80), carried forward across gaps.
        $this->assertSame([0, 20, 30, 30, 55, 80], $byLabel['Scheduled plan']);
        // Original's full-length 40-share point on 2026-03-01 must NOT appear.
        $this->assertNotContains(40, $byLabel['Scheduled plan']);
        // Actual repayments carried forward independently.
        $this->assertSame([null, 20, 20, 35, 35, 35], $byLabel['Actual repayments']);
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
        // Actual also anchored at the origination 0, flat until the repayment.
        $this->assertSame([0, 0, 15, 15], $byLabel['Actual repayments']);
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
