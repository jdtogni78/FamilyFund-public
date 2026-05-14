<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Regression test for bug #4 in credit_lines_bugs_found.md:
 *
 * The trajectory chart partial's <img src> must read from
 * config('quickchart.base_url') so it can be reconfigured per
 * environment (browser vs SSR). Hardcoding `http://quickchart:3400`
 * breaks browser rendering outside Docker.
 */
class CreditLineTrajectoryChartUrlTest extends TestCase
{
    public function test_trajectory_chart_partial_reads_quickchart_public_url_from_config(): void
    {
        // Override the config to a sentinel value.
        config([
            'quickchart.public_url' => 'http://example.test:9999',
            'quickchart.base_url'   => 'http://quickchart-ssr:3400',
        ]);

        $rendered = view('account_credit_lines._trajectory_chart', [
            'trajectory' => [
                'original_plan'         => [
                    ['date' => '2026-01-01', 'cumulative_shares' => 10],
                    ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ],
                'historical_plans'      => [],
                'current_plan'          => [
                    ['date' => '2026-01-01', 'cumulative_shares' => 10],
                    ['date' => '2026-02-01', 'cumulative_shares' => 20],
                ],
                'actual_repayments'     => [],
                'projected_payoff_date' => null,
                'planned_payoff_date'   => '2026-02-01',
                'variance_days'         => null,
            ],
        ])->render();

        $this->assertStringContainsString(
            'http://example.test:9999/chart?',
            $rendered,
            'Trajectory partial should build its chart <img src> from config("quickchart.base_url").'
        );
        $this->assertStringNotContainsString(
            'http://quickchart:3400',
            $rendered,
            'Trajectory partial must not hardcode the docker container hostname.'
        );
    }
}
