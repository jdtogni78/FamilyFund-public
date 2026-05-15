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
     * Wave-2 re-review (2026-05-15): UC-46 admin "create transaction from
     * scratch" allows arbitrary timestamps. If an admin backdates a BOR/REP
     * to before the currently-open BOR balance row's start_dt, the previous
     * code would close that row with end_dt=$asOf — producing a temporally
     * inverted row (end_dt < start_dt). Now guarded: refuses loudly.
     */
    public function test_backdated_asof_before_existing_start_dt_throws(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        // Draw today → opens a BOR row with start_dt = today.
        $today = Carbon::today()->toDateString();
        $this->drawService->open($account, 50.0, 6, 'monthly');

        // Now try to update the aggregate from a date BEFORE today.
        // The admin in this scenario is creating a backdated REP whose
        // timestamp predates the existing open BOR row.
        $backdated = Carbon::today()->subDays(7)->toDateString();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Backdated BOR\/REP rejected/');

        $this->calculator->updateAggregateBorBalance(
            $account,
            $backdated,
            999_999_999  // bogus tx id — won't be used; we expect to throw before insert
        );
    }
}
