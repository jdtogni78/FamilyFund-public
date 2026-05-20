<?php

namespace Tests\Unit\Services\CreditLine\Support;

use App\Models\AccountBalance;
use App\Models\Asset;
use App\Models\TransactionExt;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Reverse\ReversalOutstandingRecomputer;
use App\Services\CreditLine\Reverse\ReverseService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\CreditLineBalanceTracker;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Wave-2 review (2026-05-14): when a draw and a repay land on the same
 * calendar date, `updateAggregateBorBalance()` used to close the open BOR
 * row (end_dt=today) and insert a fresh one starting today, leaving a
 * zero-length row that polluted historical reads. Now it updates in
 * place when `start_dt === asOf`.
 */
class OutstandingCalculatorSameDayTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $drawService;
    private RepayService $repayService;
    private OutstandingCalculator $calculator;

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

        $this->calculator = new OutstandingCalculator();
        $tracker = new CreditLineBalanceTracker();
        $this->drawService  = new DrawService(new AmortizationScheduleBuilder(), $this->calculator, $tracker);
        $this->repayService = new RepayService($this->calculator, $tracker);
    }

    private function seedOwnBalance($account, float $shares, ?Carbon $startDate = null): void
    {
        $startDate = $startDate ?? Carbon::today();
        $tran = $this->factory->createTransaction(
            $shares * 10,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            $startDate->toDateString()
        );
        $tran->shares = $shares;
        $tran->save();

        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => $startDate->toDateString(),
            'end_dt'         => '9999-12-31',
        ]);
    }

    public function test_same_day_draw_and_repay_leaves_exactly_one_open_bor_row(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);

        $line = $this->drawService->open($account, 50.0, 6, 'monthly');
        $this->repayService->repay($line, 20.0);

        $borRows = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->get();

        // Exactly one open row, balance = 30.
        $open = $borRows->filter(fn ($r) => $r->end_dt && $r->end_dt->toDateString() === '9999-12-31')->values();
        $this->assertCount(1, $open, 'Should have exactly one open BOR row after same-day draw+repay.');
        $this->assertEquals(30.0, round((float) $open[0]->shares, 4));

        // No zero-length rows (start_dt == end_dt and end_dt != open-sentinel).
        foreach ($borRows as $row) {
            $endIsSentinel = $row->end_dt && $row->end_dt->toDateString() === '9999-12-31';
            if (!$endIsSentinel) {
                $this->assertNotEquals(
                    $row->start_dt->toDateString(),
                    $row->end_dt->toDateString(),
                    'No BOR row should be zero-length (start_dt==end_dt).'
                );
            }
        }
    }

    public function test_same_day_draw_then_full_repay_leaves_no_open_bor_row(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);

        $line = $this->drawService->open($account, 50.0, 6, 'monthly');
        $this->repayService->repay($line, 50.0);

        $borRows = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->get();

        $open = $borRows->filter(fn ($r) => $r->end_dt && $r->end_dt->toDateString() === '9999-12-31')->values();
        $this->assertCount(0, $open, 'No open BOR row should remain when same-day full repay clears outstanding.');

        // The originally-opened-and-closed-same-day row should not be persisted
        // either — it's a zero-length artifact.
        foreach ($borRows as $row) {
            $endIsSentinel = $row->end_dt && $row->end_dt->toDateString() === '9999-12-31';
            if (!$endIsSentinel) {
                $this->assertNotEquals(
                    $row->start_dt->toDateString(),
                    $row->end_dt->toDateString(),
                    'No BOR row should be zero-length.'
                );
            }
        }
    }

    /**
     * Wave-2 re-review (2026-05-17): UC-46 admin "create transaction from
     * scratch" and routine late-payment reconciliation legitimately carry a
     * settlement date in the past. The aggregate BOR row is a single
     * open-ended "current state" projection and cannot rewrite its closed
     * history, so a backdated $asOf before the open row's start_dt is
     * *clamped forward* to that start_dt (rather than refused or allowed to
     * invert the row). Exactly one open row survives, with the recomputed
     * total and no temporally inverted (end_dt < start_dt) rows.
     */
    public function test_backdated_asof_before_existing_start_dt_clamps_forward(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        // Draw today → opens a BOR row with start_dt = today.
        $today = Carbon::today()->toDateString();
        $line  = $this->drawService->open($account, 50.0, 6, 'monthly');

        // A backdated REP whose settlement date predates the open BOR row.
        $backdated = Carbon::today()->subDays(7)->toDateString();
        $this->repayService->repay($line, 20.0, Carbon::parse($backdated));

        $borRows = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->get();

        // Exactly one open row, clamped to today's start_dt, total = 30.
        $open = $borRows->filter(fn ($r) => $r->end_dt && $r->end_dt->toDateString() === '9999-12-31')->values();
        $this->assertCount(1, $open, 'Should have exactly one open BOR row after a backdated repay.');
        $this->assertEquals($today, $open[0]->start_dt->toDateString(), 'Open BOR row start_dt clamped forward, not backdated.');
        $this->assertEquals(30.0, round((float) $open[0]->shares, 4));

        // No temporally inverted rows.
        foreach ($borRows as $row) {
            $endIsSentinel = $row->end_dt && $row->end_dt->toDateString() === '9999-12-31';
            if (!$endIsSentinel) {
                $this->assertTrue(
                    $row->end_dt->toDateString() >= $row->start_dt->toDateString(),
                    'No BOR row should be temporally inverted (end_dt < start_dt).'
                );
            }
        }
    }

    /**
     * Backdated DRAW: opening a new credit line with an origination date that
     * predates an existing open BOR row used to be refused. Now the draw is
     * spliced into the BOR chain — the open row's shares are bumped to the
     * new aggregate, and a closed historical row covers [$asOf, openRow.start_dt)
     * so as-of reads in that window see only the new draw's contribution.
     */
    public function test_backdated_draw_splices_historical_bor_row(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 400.0, Carbon::today()->subDays(30));

        // First draw 10 days ago — opens the BOR row at that date.
        $firstOrigination = Carbon::today()->subDays(10);
        $this->drawService->open($account, 100.0, 12, 'monthly', null, $firstOrigination);

        // Second draw backdated to 20 days ago — predates the open BOR row.
        $secondOrigination = Carbon::today()->subDays(20);
        $this->drawService->open($account, 50.0, 12, 'monthly', null, $secondOrigination);

        $borRows = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->orderBy('start_dt', 'asc')
            ->get();

        // Exactly two BOR rows: a closed historical splice + the open row.
        $this->assertCount(2, $borRows, 'Backdated draw should splice in one new closed BOR row.');

        $historical = $borRows[0];
        $open       = $borRows[1];

        // Historical row covers [secondOrigination, firstOrigination) with
        // only the backdated draw's principal contributing.
        $this->assertEquals($secondOrigination->toDateString(), $historical->start_dt->toDateString());
        $this->assertEquals($firstOrigination->toDateString(), $historical->end_dt->toDateString());
        $this->assertEquals(50.0, round((float) $historical->shares, 4));

        // Open row starts at the original first-draw date and now reflects
        // the combined outstanding (100 + 50).
        $this->assertEquals($firstOrigination->toDateString(), $open->start_dt->toDateString());
        $this->assertEquals('9999-12-31', $open->end_dt->toDateString());
        $this->assertEquals(150.0, round((float) $open->shares, 4));

        // No temporally inverted rows.
        foreach ($borRows as $row) {
            $this->assertTrue(
                $row->end_dt->toDateString() > $row->start_dt->toDateString(),
                'No BOR row should be temporally inverted or zero-length.'
            );
        }
    }

    /**
     * Backdated DRAW landing INSIDE a closed BOR row's range: the straddled
     * row is split at $asOf, the "after" piece carries the new draw's delta,
     * and every later row in the chain (including the open row) is bumped.
     * Mirrors the real account-7 scenario that prompted this fix.
     */
    public function test_backdated_draw_splits_straddled_closed_row(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 500.0, Carbon::today()->subDays(40));

        // First draw 30 days ago (line A, 80 shares).
        $dayA = Carbon::today()->subDays(30);
        $lineA = $this->drawService->open($account, 80.0, 12, 'monthly', null, $dayA);

        // Partial repay 20 days ago (40 shares) → closes the open row and
        // opens a new one with shares=40 starting at that date.
        $dayB = Carbon::today()->subDays(20);
        $this->repayService->repay($lineA, 40.0, $dayB);

        // Backdate a NEW draw to 25 days ago — that lands INSIDE the first
        // (now-closed) BOR row's range [dayA, dayB).
        $dayC = Carbon::today()->subDays(25);
        $this->drawService->open($account, 30.0, 12, 'monthly', null, $dayC);

        $borRows = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->orderBy('start_dt', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Expected chain:
        //   [dayA, dayC) shares=80   (untouched front of split)
        //   [dayC, dayB) shares=110  (back of split + new draw's 30)
        //   [dayB, ∞)    shares=70   (open row bumped: 40 + 30)
        $this->assertCount(3, $borRows);

        $this->assertEquals($dayA->toDateString(), $borRows[0]->start_dt->toDateString());
        $this->assertEquals($dayC->toDateString(), $borRows[0]->end_dt->toDateString());
        $this->assertEquals(80.0, round((float) $borRows[0]->shares, 4));

        $this->assertEquals($dayC->toDateString(), $borRows[1]->start_dt->toDateString());
        $this->assertEquals($dayB->toDateString(), $borRows[1]->end_dt->toDateString());
        $this->assertEquals(110.0, round((float) $borRows[1]->shares, 4));

        $this->assertEquals($dayB->toDateString(), $borRows[2]->start_dt->toDateString());
        $this->assertEquals('9999-12-31',          $borRows[2]->end_dt->toDateString());
        $this->assertEquals(70.0, round((float) $borRows[2]->shares, 4));
    }
}
