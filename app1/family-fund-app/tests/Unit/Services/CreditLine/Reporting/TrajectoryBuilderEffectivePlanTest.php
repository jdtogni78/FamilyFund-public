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
 * Regression for the three payoff-trajectory "planned line" defects:
 *
 *  (1) mismatched per-generation baselines — the original generation ran
 *      cumulative from 0 while later generations started at
 *      principal − outstanding_at_adjustment, so the spliced line dipped
 *      then climbed at every readjust;
 *  (2) the splice compared an adjustment's adjusted_at (wall-clock write
 *      time) against payment due_dates — two different time axes;
 *  (3) carrying that mismatched series forward turned the resulting gaps
 *      into a sawtooth.
 *
 * The fix consolidates the splice into TrajectoryBuilder: the schedule
 * actually in force over time is exactly the set of non-cancelled
 * payment rows (Prompt 2's contract — ReadjustService cancels rows due
 * on/after effective_date and inserts the new generation from there).
 * Walking them in due_date order with one running cumulative yields a
 * single continuous, monotonic-non-decreasing series with no gaps that
 * ends at principal_shares, switching generations at each adjustment's
 * effective_date — never at adjusted_at, never resetting the baseline.
 */
class TrajectoryBuilderEffectivePlanTest extends TestCase
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

    private function makeLine(float $principal = 120.0): AccountCreditLine
    {
        return AccountCreditLine::create([
            'account_id'         => $this->factory->userAccount->id,
            'principal_shares'   => $principal,
            'outstanding_shares' => $principal,
            'term_months'        => 6,
            'origination_date'   => '2026-01-01',
            'maturity_date'      => '2026-07-01',
            'payment_frequency'  => 'monthly',
            'status'             => 'active',
        ]);
    }

    private function pay(AccountCreditLine $line, string $due, float $shares, string $status): void
    {
        CreditLinePayment::create([
            'account_credit_line_id' => $line->id,
            'due_date'               => $due,
            'shares_due'             => $shares,
            'status'                 => $status,
        ]);
    }

    /** Without adjustments the effective plan is just the original schedule. */
    public function test_no_adjustment_effective_plan_is_the_original_cumulative(): void
    {
        $line = $this->makeLine(120.0);
        for ($i = 1; $i <= 6; $i++) {
            $this->pay($line, Carbon::parse('2026-01-01')->addMonths($i)->toDateString(),
                20.0, CreditLinePayment::STATUS_SCHEDULED);
        }

        $plan = $this->builder->build($line)['effective_plan'];

        $this->assertSame(
            [20.0, 40.0, 60.0, 80.0, 100.0, 120.0],
            array_column($plan, 'cumulative_shares')
        );
        $this->assertSame('2026-07-01', end($plan)['date']);
    }

    /**
     * The core defect scenario: a readjust whose adjusted_at (wall-clock)
     * is well before its effective_date. Old rows due before the effective
     * date stay in force; rows on/after are cancelled and a new generation
     * is inserted from the effective date.
     */
    public function test_effective_plan_is_continuous_monotone_and_switches_on_effective_date(): void
    {
        $line = $this->makeLine(120.0);

        // Original 6 monthly rows of 20, due 2026-02-01 .. 2026-07-01.
        for ($i = 1; $i <= 6; $i++) {
            $this->pay($line, Carbon::parse('2026-01-01')->addMonths($i)->toDateString(),
                20.0, CreditLinePayment::STATUS_SCHEDULED);
        }

        // First installment was repaid → that row is PAID (still in force,
        // not cancelled). 20 of 120 repaid → outstanding 100.
        CreditLinePayment::where('account_credit_line_id', $line->id)
            ->whereDate('due_date', '2026-02-01')
            ->update(['status' => CreditLinePayment::STATUS_PAID]);

        // Readjust written 2026-03-20 (adjusted_at) but EFFECTIVE 2026-04-01.
        // Mirror ReadjustService: cancel rows due on/after effective_date,
        // keep the rows due before it, snapshot outstanding = 100.
        $effectiveDate = '2026-04-01';
        CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->whereDate('due_date', '>=', $effectiveDate)
            ->update(['status' => CreditLinePayment::STATUS_CANCELLED]);

        CreditLineAdjustment::create([
            'account_credit_line_id'           => $line->id,
            'adjusted_at'                      => '2026-03-20 09:00:00',
            'effective_date'                   => $effectiveDate,
            'adjusted_by_user_id'              => null,
            'outstanding_shares_at_adjustment' => 100,
            'old_term_months'                  => 6,
            'new_term_months'                  => 12,
            'old_payment_frequency'            => 'monthly',
            'new_payment_frequency'            => 'monthly',
            'old_maturity_date'                => '2026-07-01',
            'new_maturity_date'                => '2027-02-01',
            'old_planned_payoff_date'          => '2026-07-01',
            'new_planned_payoff_date'          => '2027-02-01',
        ]);

        // New generation: outstanding 100 over 10 monthly rows of 10,
        // anchored at the effective date → due 2026-05-01 .. 2027-02-01.
        for ($i = 1; $i <= 10; $i++) {
            $this->pay($line, Carbon::parse('2026-04-01')->addMonths($i)->toDateString(),
                10.0, CreditLinePayment::STATUS_SCHEDULED);
        }

        $plan = $this->builder->build($line)['effective_plan'];
        $cum  = array_column($plan, 'cumulative_shares');
        $byDate = array_column($plan, 'cumulative_shares', 'date');

        // (3)+(1) Monotonic non-decreasing — no dip/sawtooth at the boundary.
        $prev = -INF;
        foreach ($cum as $v) {
            $this->assertGreaterThanOrEqual($prev, $v,
                'effective_plan must be monotonic non-decreasing (no dip at readjust)');
            $prev = $v;
        }

        // (2) Boundary is the effective_date, not adjusted_at. The 2026-03-01
        // old row (due before effective_date) survives → cumulative 40 there.
        // If the splice had keyed off adjusted_at (2026-03-20) it would have
        // been dropped.
        $this->assertArrayHasKey('2026-03-01', $byDate);
        $this->assertSame(40.0, $byDate['2026-03-01']);

        // (1) Cumulative carries across the boundary — the first new-gen
        // point continues from 40 (+10), it is NOT reset to
        // principal − outstanding_at_adjustment (= 120 − 100 = 20, which
        // would have produced 30 here, a dip).
        $this->assertArrayHasKey('2026-05-01', $byDate);
        $this->assertSame(50.0, $byDate['2026-05-01']);

        // No cancelled-row date leaks in (the superseded 2026-04-01 row).
        $this->assertArrayNotHasKey('2026-04-01', $byDate);

        // Ends at principal_shares.
        $this->assertSame(120.0, end($cum));

        // No gaps: every point carries a numeric cumulative and dates are
        // strictly increasing.
        $dates = array_column($plan, 'date');
        $this->assertSame($dates, array_values(array_unique($dates)));
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates, 'points must be ordered by due_date');
        foreach ($cum as $v) {
            $this->assertIsNumeric($v);
        }
    }
}
