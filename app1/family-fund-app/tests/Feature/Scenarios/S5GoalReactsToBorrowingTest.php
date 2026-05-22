<?php

namespace Tests\Feature\Scenarios;

use App\Models\GoalExt;
use App\Services\GoalCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S5 — Goals react to borrowing.
 *
 * Canonical rule (see memory `goal-current-is-net`): a goal's "Current" is
 * always net = OWN − BOR, never gross OWN. Borrowed shares aren't held; counting
 * them as still-held inflates progress and misleads.
 *
 * Two layers covered here:
 *
 *  (a) Pure math — AccountExt::sharesAsOf / valueAsOf already net OWN − BOR.
 *      A draw must immediately drop both, and a full repay must restore them.
 *      These assertions pass today.
 *
 *  (b) Goal-progress rendering — the `progress['current']` array consumed by
 *      goals/progress_summary.blade.php now flows through GoalCalculationService
 *      (shipped 2026-05-20 via commit 9f9ce944), which returns net for `current`
 *      and the gross figure on a separate `current_gross` key.
 */
class S5GoalReactsToBorrowingTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    public function test_shares_as_of_drops_after_draw_and_recovers_after_repay(): void
    {
        $own = 500.0;
        $this->s->withBorrowingPower('bE', $own);
        $this->s->withGoal('bE', 'Retirement', 10_000);

        // t0: no borrowing → net == OWN
        $this->assertEqualsWithDelta($own, $this->s->sharesAsOf('bE'), 0.0001);

        // t1: draw 200 → net == OWN − 200
        $line = $this->s->openLine('bE', 200, 12, 'monthly', null, 'S5 draw');
        $this->assertEqualsWithDelta($own - 200, $this->s->sharesAsOf('bE'), 0.0001,
            'after a draw, sharesAsOf must drop by exactly the borrowed amount');

        // t2: repay in full → net restored to OWN
        $this->s->repay($line, 200);
        $this->assertEqualsWithDelta($own, $this->s->sharesAsOf('bE'), 0.0001,
            'after full repay, sharesAsOf must recover to gross OWN');
    }

    public function test_value_as_of_tracks_net_shares_after_borrowing(): void
    {
        $this->s->withBorrowingPower('bE', 500.0);
        $account = $this->s->account('bE');

        $shareValue = $account->shareValueAsOf(Carbon::today()->toDateString());
        $valueBefore = $account->valueAsOf(Carbon::today()->toDateString());

        $line = $this->s->openLine('bE', 200, 12, 'monthly', null, 'S5 value');
        $valueAfter = $account->valueAsOf(Carbon::today()->toDateString());

        $this->assertEqualsWithDelta(
            $valueBefore - 200 * $shareValue,
            $valueAfter,
            0.0001,
            'valueAsOf must reflect net = (OWN − BOR) × share_value after a draw'
        );

        // Repay restores valueAsOf to gross.
        $this->s->repay($line, 200);
        $this->assertEqualsWithDelta($valueBefore, $account->valueAsOf(Carbon::today()->toDateString()), 0.0001);
    }

    public function test_two_goals_total_and_pct_both_track_net_via_value_as_of(): void
    {
        // Same `valueAsOf` underpins both goal target_types (total + 4pct),
        // so the underlying math is consistent across goal flavors. We assert
        // the input to the goal-progress pipeline (account.valueAsOf), not the
        // pipeline's output — see the skipped test below for the latter.
        $this->s->withBorrowingPower('bE', 500.0);
        $this->s->withGoal('bE', 'House', 5_000, targetType: 'total');
        $this->s->withGoal('bE', 'FIRE',  10_000, targetType: '4pct', targetPct: 4);
        $account = $this->s->account('bE');

        $netBefore = $account->valueAsOf(Carbon::today()->toDateString());
        $this->s->openLine('bE', 150, 12, 'monthly', null, 'S5 multi-goal');
        $netAfter = $account->valueAsOf(Carbon::today()->toDateString());

        $shareValue = $account->shareValueAsOf(Carbon::today()->toDateString());
        $this->assertEqualsWithDelta(
            $netBefore - 150 * $shareValue,
            $netAfter,
            0.0001,
            'both goals draw their "current" from valueAsOf; one number, two presentations'
        );
    }

    /**
     * Canonical rule (memory: goal-current-is-net): `progress['current']` must
     * equal valueAsOf (net). `progress['current_gross']` is exposed for legacy
     * callers but views default to `current`. Validates the shipped behavior
     * of GoalCalculationService end-to-end (no HTTP — the service is the
     * single seam where the rule lives).
     */
    public function test_goal_progress_current_reflects_net_and_current_gross_carries_legacy(): void
    {
        $this->s->withBorrowingPower('bE', 500.0);
        $goal = $this->s->withGoal('bE', 'Retirement', 10_000);
        $line = $this->s->openLine('bE', 200, 12, 'monthly', null, 'S5 progress');
        $account = $this->s->account('bE');

        $progress = app(GoalCalculationService::class)
            ->progressFor($account, GoalExt::find($goal->id), Carbon::today());

        $shareValue   = $account->shareValueAsOf(Carbon::today()->toDateString());
        $expectedNet  = $account->valueAsOf(Carbon::today()->toDateString());
        $expectedGross = 500.0 * $shareValue;
        $expectedBorrowedValue = 200.0 * $shareValue;

        $this->assertEqualsWithDelta($expectedNet,   $progress['current']['value'],        0.0001,
            'progress.current.value must equal net valueAsOf (OWN − BOR) × shareValue');
        $this->assertEqualsWithDelta($expectedGross, $progress['current_gross']['value'],  0.0001,
            'progress.current_gross.value must equal OWN × shareValue (legacy callers only)');
        $this->assertEqualsWithDelta($expectedBorrowedValue, $progress['borrowed_value'],  0.0001,
            'borrowed_value should surface the dollar value of outstanding borrowed shares');
        $this->assertEqualsWithDelta(200.0, $progress['borrowed_shares'], 0.0001);

        // current and current_gross diverge precisely by the borrowed delta.
        $this->assertEqualsWithDelta(
            $progress['current_gross']['value'] - $progress['current']['value'],
            $progress['borrowed_value'],
            0.0001,
            'gross − net must equal borrowed_value'
        );
    }
}
