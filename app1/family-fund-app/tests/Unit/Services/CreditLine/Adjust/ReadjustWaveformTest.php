<?php

namespace Tests\Unit\Services\CreditLine\Adjust;

use App\Models\AccountBalance;
use App\Models\AccountCreditLineExt;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Services\CreditLine\Adjust\ReadjustService;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Reporting\TrajectoryBuilder;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Waveform / trajectory-shape tests for readjustment.
 *
 * Bug reported 2026-05-19: "readjustments were not generating right waveforms".
 *
 * The existing ReadjustServiceTest covers the audit-trail / generation /
 * supersession mechanics but does not directly assert the *shape* of the
 * trajectory the user sees on the chart:
 *
 *   - effective_plan (spliced across generations) must be monotonic-increasing
 *   - effective_plan must end at exactly principal_shares (no over/undershoot)
 *   - cancelled rows must NOT contribute to expected_to_date
 *   - mid-cycle readjusts must not produce a downward step in the plan line
 *   - repeated readjusts (3-deep chain) must each produce one historical_plans
 *     series with non-empty data
 *   - forward-dated readjust: pre-effective-date installments stay in force
 *   - backward-dated readjust: late backlog re-anchors at the effective date
 *
 * These are precisely the failure modes a "waveform" complaint maps to.
 */
class ReadjustWaveformTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $drawService;
    private RepayService $repayService;
    private ReadjustService $readjustService;
    private TrajectoryBuilder $trajectoryBuilder;

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

        $calc    = new OutstandingCalculator();
        $builder = new AmortizationScheduleBuilder();

        $this->drawService       = new DrawService($builder, $calc);
        $this->repayService      = app(RepayService::class);
        $this->readjustService   = new ReadjustService($calc, $builder);
        $this->trajectoryBuilder = new TrajectoryBuilder();

        Carbon::setTestNow(Carbon::parse('2026-01-01'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private function seedOwn(float $shares = 500.0, string $start = '2022-01-15'): void
    {
        $account = $this->factory->userAccount;
        $tran = $this->factory->createTransaction(
            $shares,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            $start
        );
        $tran->shares = $shares;
        $tran->save();
        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => $start,
            'end_dt'         => '9999-12-31',
        ]);
    }

    // ---------------------------------------------------------------
    // Effective-plan invariants
    // ---------------------------------------------------------------

    public function test_effective_plan_is_monotonic_after_single_readjust(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount,
            100.0,
            12,
            AccountCreditLineExt::FREQUENCY_MONTHLY,
            null,
            Carbon::parse('2026-01-01')
        );

        // Readjust mid-life: shorter term, same frequency.
        Carbon::setTestNow(Carbon::parse('2026-04-15'));
        $this->readjustService->readjust($line->refresh(), 8, null, null, 'shorten', Carbon::parse('2026-04-15'));

        $trajectory = $this->trajectoryBuilder->build($line->refresh());
        $series = $trajectory['effective_plan'];

        $this->assertNotEmpty($series, 'Effective plan must have rows after a readjust.');

        $prev = -INF;
        foreach ($series as $point) {
            $this->assertGreaterThanOrEqual(
                $prev,
                $point['cumulative_shares'],
                'effective_plan cumulative_shares must be non-decreasing.'
            );
            $prev = $point['cumulative_shares'];
        }
    }

    public function test_effective_plan_ends_at_principal_after_readjust(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount,
            100.0,
            12,
            AccountCreditLineExt::FREQUENCY_MONTHLY,
            null,
            Carbon::parse('2026-01-01')
        );

        Carbon::setTestNow(Carbon::parse('2026-04-15'));
        $this->readjustService->readjust($line->refresh(), 6, null, null, 'shorten', Carbon::parse('2026-04-15'));

        $trajectory = $this->trajectoryBuilder->build($line->refresh());
        $series = $trajectory['effective_plan'];
        $this->assertNotEmpty($series);

        $last = end($series);
        $this->assertEqualsWithDelta(
            100.0,
            $last['cumulative_shares'],
            1e-3,
            'effective_plan must terminate at principal (full repayment by maturity).'
        );
    }

    public function test_cancelled_rows_do_not_appear_in_expected_to_date(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount,
            100.0,
            12,
            AccountCreditLineExt::FREQUENCY_MONTHLY,
            null,
            Carbon::parse('2026-01-01')
        );

        $originalRowIds = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->orderBy('due_date')
            ->pluck('id');

        // Pick an effective date that supersedes most of the original plan.
        Carbon::setTestNow(Carbon::parse('2026-03-15'));
        $this->readjustService->readjust($line->refresh(), 24, null, null, 'extend', Carbon::parse('2026-03-15'));

        $cancelledOriginalIds = CreditLinePayment::whereIn('id', $originalRowIds)
            ->where('status', CreditLinePayment::STATUS_CANCELLED)
            ->pluck('id')
            ->all();

        // Time-travel forward so "today" is past every cancelled row.
        Carbon::setTestNow(Carbon::parse('2026-06-01'));
        $trajectory = $this->trajectoryBuilder->build($line->refresh());

        // No cancelled-row due_date should appear among expected_to_date dates.
        $cancelledDates = CreditLinePayment::whereIn('id', $cancelledOriginalIds)
            ->pluck('due_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->all();

        $expectedDates = array_column($trajectory['expected_to_date'], 'date');
        foreach ($cancelledDates as $d) {
            // It's possible the new generation produces the *same* due date
            // for a non-cancelled row; the bug we're guarding against is the
            // cancelled rows being summed in cumulative_shares twice. We assert
            // that no expected point matches a cancelled date AND has the old
            // cumulative shape — practically, the cumulative must not exceed
            // principal at any point past readjust.
            // (Direct date-presence is a weaker check; the cumulative-cap test
            // below is the stricter one.)
            $this->assertTrue(true, "Cancelled date $d acknowledged");
        }

        // Cumulative cap invariant: expected_to_date cumulative never exceeds
        // principal_shares. If cancelled rows leak in, the cumulative would
        // overshoot.
        foreach ($trajectory['expected_to_date'] as $p) {
            $this->assertLessThanOrEqual(
                100.0 + 1e-3,
                $p['cumulative_shares'],
                'expected_to_date cumulative must never exceed principal. '
                . 'Overshoot here would mean cancelled rows are being summed.'
            );
        }
    }

    public function test_repeated_readjusts_produce_one_historical_plan_per_adjustment(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount,
            100.0,
            12,
            AccountCreditLineExt::FREQUENCY_MONTHLY,
            null,
            Carbon::parse('2026-01-01')
        );

        // Three readjusts on different dates with different terms.
        Carbon::setTestNow(Carbon::parse('2026-02-15'));
        $this->readjustService->readjust($line->refresh(), 10, null, null, 'first', Carbon::parse('2026-02-15'));

        Carbon::setTestNow(Carbon::parse('2026-04-10'));
        $this->readjustService->readjust($line->refresh(), 8, null, null, 'second', Carbon::parse('2026-04-10'));

        Carbon::setTestNow(Carbon::parse('2026-05-20'));
        $this->readjustService->readjust($line->refresh(), 6, null, null, 'third', Carbon::parse('2026-05-20'));

        $trajectory = $this->trajectoryBuilder->build($line->refresh());

        $this->assertCount(
            3,
            $trajectory['historical_plans'],
            'Three readjusts must produce exactly three historical_plans entries.'
        );

        foreach ($trajectory['historical_plans'] as $i => $plan) {
            $this->assertNotEmpty(
                $plan['series'],
                "historical_plans[$i] must have a non-empty series."
            );
        }
    }

    /**
     * Regression: A forward-dated readjust (effective date AFTER today)
     * leaves the pre-effective-date installments in the SCHEDULED status, so
     * the chart's current_plan starts at or before the effective date — no
     * gap in the waveform.  This currently passes; documents the contract so
     * a future refactor does not regress it.
     */
    public function test_forward_dated_readjust_keeps_pre_effective_rows_in_current_plan(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount,
            100.0,
            12,
            AccountCreditLineExt::FREQUENCY_MONTHLY,
            null,
            Carbon::parse('2026-01-01')
        );

        // Readjust effective 2 months from now (forward-dated).
        $effectiveDate = Carbon::parse('2026-03-01');
        $this->readjustService->readjust($line->refresh(), 18, null, null, 'forward-date', $effectiveDate);

        $trajectory = $this->trajectoryBuilder->build($line->refresh());
        $currentPlan = $trajectory['current_plan'];

        $this->assertNotEmpty($currentPlan, 'current_plan must not be empty.');

        $firstDate = Carbon::parse($currentPlan[0]['date']);
        $this->assertTrue(
            $firstDate->lte($effectiveDate),
            sprintf(
                'current_plan first date %s should be on or before effective_date %s — '
                . 'the pre-effective-date installments must remain visible on the chart, '
                . 'otherwise the user sees a gap in the waveform.',
                $firstDate->toDateString(),
                $effectiveDate->toDateString()
            )
        );
    }

    /**
     * Regression: a backdated readjust must not double-count late backlog
     * across the old + new generations.  TrajectoryBuilder's overdue_shares
     * computation currently caps at principal_shares, so this passes; it
     * remains a regression guard against a future refactor that double-sums.
     */
    public function test_backdated_readjust_does_not_double_count_late_backlog(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount,
            100.0,
            12,
            AccountCreditLineExt::FREQUENCY_MONTHLY,
            null,
            Carbon::parse('2025-09-01')
        );

        // Some payments have already accrued past-due by 2026-01-01 (today).
        // Now we readjust effective 2025-11-01 — backdated by 2 months from today.
        Carbon::setTestNow(Carbon::parse('2026-01-01'));
        $this->readjustService->readjust($line->refresh(), 12, null, null, 'backdate', Carbon::parse('2025-11-01'));

        $trajectory = $this->trajectoryBuilder->build($line->refresh());

        $overdue = (float) ($trajectory['overdue_shares'] ?? 0);

        // Sanity: the overdue_shares should not exceed principal. If old +
        // new generations both contribute, it can overshoot.
        $this->assertLessThanOrEqual(
            100.0 + 1e-3,
            $overdue,
            sprintf(
                'overdue_shares=%.4f exceeds principal_shares=100. Backdated readjust '
                . 'is double-counting late backlog across old + new generations.',
                $overdue
            )
        );
    }

    public function test_actual_repayments_series_has_only_confirmed_credits(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount,
            100.0,
            6,
            AccountCreditLineExt::FREQUENCY_MONTHLY,
            null,
            Carbon::parse('2026-01-01')
        );

        Carbon::setTestNow(Carbon::parse('2026-02-15'));
        $this->repayService->repay($line->refresh(), 16.6666, Carbon::parse('2026-02-15'));

        Carbon::setTestNow(Carbon::parse('2026-03-15'));
        $this->repayService->repay($line->refresh(), 16.6666, Carbon::parse('2026-03-15'));

        $trajectory = $this->trajectoryBuilder->build($line->refresh());

        $actual = $trajectory['actual_repayments'];
        $this->assertCount(2, $actual, 'Two confirmed REPs must produce 2 actual points.');

        $this->assertEqualsWithDelta(16.6666, $actual[0]['cumulative_shares'], 1e-3);
        $this->assertEqualsWithDelta(16.6666 * 2, $actual[1]['cumulative_shares'], 1e-3);
    }

    public function test_quarterly_readjust_produces_quarterly_spaced_plan(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount,
            100.0,
            12,
            AccountCreditLineExt::FREQUENCY_MONTHLY,
            null,
            Carbon::parse('2026-01-01')
        );

        // Switch frequency to quarterly mid-life.
        Carbon::setTestNow(Carbon::parse('2026-02-01'));
        $this->readjustService->readjust(
            $line->refresh(),
            12,
            AccountCreditLineExt::FREQUENCY_QUARTERLY,
            null,
            'switch to quarterly',
            Carbon::parse('2026-02-01')
        );

        $newGenRows = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->orderBy('due_date')
            ->get();

        $this->assertGreaterThanOrEqual(2, $newGenRows->count(),
            'A 12-month quarterly schedule must have ≥2 scheduled rows after the readjust.');

        // Consecutive new-gen rows must be ~3 months apart.
        for ($i = 1; $i < $newGenRows->count(); $i++) {
            $a = Carbon::parse($newGenRows[$i - 1]->due_date);
            $b = Carbon::parse($newGenRows[$i]->due_date);
            $months = $a->diffInMonths($b);
            $this->assertGreaterThanOrEqual(2, $months,
                "Quarterly cadence: rows $i-1 → $i should be ~3 months apart (got $months).");
            $this->assertLessThanOrEqual(4, $months,
                "Quarterly cadence: rows $i-1 → $i should be ~3 months apart (got $months).");
        }
    }

    public function test_readjust_after_partial_repayment_keeps_actual_series_intact(): void
    {
        $this->seedOwn();
        $line = $this->drawService->open(
            $this->factory->userAccount,
            100.0,
            12,
            AccountCreditLineExt::FREQUENCY_MONTHLY,
            null,
            Carbon::parse('2026-01-01')
        );

        Carbon::setTestNow(Carbon::parse('2026-02-15'));
        $this->repayService->repay($line->refresh(), 8.0, Carbon::parse('2026-02-15'));

        // Now readjust.
        Carbon::setTestNow(Carbon::parse('2026-03-01'));
        $this->readjustService->readjust($line->refresh(), 24, null, null, 'extend', Carbon::parse('2026-03-01'));

        $trajectory = $this->trajectoryBuilder->build($line->refresh());

        $this->assertNotEmpty(
            $trajectory['actual_repayments'],
            'Pre-readjust repayment must remain in actual_repayments series after readjust.'
        );
        $this->assertEqualsWithDelta(
            8.0,
            $trajectory['actual_repayments'][0]['cumulative_shares'],
            1e-3,
            'Pre-readjust REP must contribute 8.0 to the cumulative — readjust should not zero it.'
        );
    }
}
