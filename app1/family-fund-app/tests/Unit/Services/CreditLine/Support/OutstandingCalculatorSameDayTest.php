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

    private function seedOwnBalance($account, float $shares): void
    {
        $tran = $this->factory->createTransaction(
            $shares * 10,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            Carbon::today()->toDateString()
        );
        $tran->shares = $shares;
        $tran->save();

        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => Carbon::today()->toDateString(),
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
}
