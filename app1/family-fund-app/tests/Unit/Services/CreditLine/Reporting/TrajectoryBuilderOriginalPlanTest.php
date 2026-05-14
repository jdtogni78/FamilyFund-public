<?php

namespace Tests\Unit\Services\CreditLine\Reporting;

use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\CreditLineAdjustment;
use App\Models\CreditLinePayment;
use App\Services\CreditLine\Reporting\TrajectoryBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Regression test for bug #2 in credit_lines_bugs_found.md:
 *
 * After readjusting a line, TrajectoryBuilder's "original_plan" series
 * double-counted new-generation rows whose created_at equaled the
 * adjustment's adjusted_at (same DB transaction → identical second-
 * precision timestamps). Fixed by changing the filter from
 * created_at->lte(firstAdjustedAt) to created_at->lt(firstAdjustedAt).
 */
class TrajectoryBuilderOriginalPlanTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private TrajectoryBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );
        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
        $this->builder = new TrajectoryBuilder();
    }

    public function test_original_plan_excludes_rows_created_at_same_instant_as_adjustment(): void
    {
        // Line opened with 6 monthly payments of 20 shares each (total 120).
        $line = AccountCreditLine::create([
            'account_id'         => $this->factory->userAccount->id,
            'principal_shares'   => 120,
            'outstanding_shares' => 120,
            'term_months'        => 6,
            'origination_date'   => '2026-01-01',
            'maturity_date'      => '2026-07-01',
            'payment_frequency'  => 'monthly',
            'status'             => 'active',
        ]);

        // 6 original-plan rows, created BEFORE the adjustment.
        $originalCreatedAt = Carbon::parse('2026-01-01 10:00:00');
        Carbon::setTestNow($originalCreatedAt);
        for ($i = 1; $i <= 6; $i++) {
            CreditLinePayment::create([
                'account_credit_line_id' => $line->id,
                'due_date'               => Carbon::parse('2026-01-01')->addMonths($i)->toDateString(),
                'shares_due'             => 20.0,
                'status'                 => CreditLinePayment::STATUS_SCHEDULED,
            ]);
        }

        // ---- Readjust: cancel originals + 12 new rows at exact same instant. ----
        $adjustedAt = Carbon::parse('2026-01-15 14:30:00');
        Carbon::setTestNow($adjustedAt);

        // Mark old rows cancelled (preserving them for the snapshot view).
        CreditLinePayment::where('account_credit_line_id', $line->id)
            ->update(['status' => CreditLinePayment::STATUS_CANCELLED]);

        // The audit row that gates the trajectory's "original" filter.
        CreditLineAdjustment::create([
            'account_credit_line_id'           => $line->id,
            'adjusted_at'                      => $adjustedAt,
            'adjusted_by_user_id'              => null,
            'outstanding_shares_at_adjustment' => 120,
            'old_term_months'                  => 6,
            'new_term_months'                  => 12,
            'old_payment_frequency'            => 'monthly',
            'new_payment_frequency'            => 'monthly',
            'old_maturity_date'                => '2026-07-01',
            'new_maturity_date'                => '2027-01-15',
            'old_planned_payoff_date'          => '2026-07-01',
            'new_planned_payoff_date'          => '2027-01-15',
        ]);

        // 12 new-plan rows of 10 shares each — created AT the same Carbon::now()
        // as the adjustment. This is the exact race the bug exploited.
        for ($i = 1; $i <= 12; $i++) {
            CreditLinePayment::create([
                'account_credit_line_id' => $line->id,
                'due_date'               => Carbon::parse('2026-01-15')->addMonths($i)->toDateString(),
                'shares_due'             => 10.0,
                'status'                 => CreditLinePayment::STATUS_SCHEDULED,
            ]);
        }

        Carbon::setTestNow(); // release time-freeze

        // ---- Assertions ----
        $trajectory = $this->builder->build($line);

        // Original plan should have exactly 6 points, each a 20-share step.
        $this->assertCount(6, $trajectory['original_plan'],
            'Original plan should have exactly 6 points (the original rows), not include the 12 new rows.');

        $originalCumulatives = array_column($trajectory['original_plan'], 'cumulative_shares');
        $this->assertEquals(
            [20.0, 40.0, 60.0, 80.0, 100.0, 120.0],
            $originalCumulatives,
            'Original plan cumulative steps should be exactly 20-share increments — the bug inflated these by adding the new-plan 10-share rows.'
        );

        // Historical plan should pick up the 12 new rows.
        $this->assertCount(1, $trajectory['historical_plans']);
        $newGenSeries = $trajectory['historical_plans'][0]['series'];
        $this->assertCount(12, $newGenSeries, 'New generation should have 12 points.');
    }
}
