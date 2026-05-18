<?php

namespace Tests\Unit\Services\CreditLine;

use App\Models\AccountBalance;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Reporting\TrajectoryBuilder;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\LateDetector;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Backdated origination + per-row manual payment registration.
 *
 * Covers:
 *  - A backdated draw generates past-due rows flagged late immediately.
 *  - A same-day draw leaves rows scheduled (no false positives).
 *  - LateDetector sweeps overdue scheduled rows.
 *  - RepayService::repayRow() targets a specific row, supports an editable
 *    amount, marks partial on a short payment, and cascades overflow.
 */
class BackdatedCreditLineTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $drawService;
    private RepayService $repayService;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2020-01-01');
        $this->factory->createUser();

        $this->drawService  = new DrawService(
            new AmortizationScheduleBuilder(),
            new OutstandingCalculator()
        );
        $this->repayService = new RepayService(new OutstandingCalculator());
    }

    public function test_backdated_draw_flags_past_due_rows_late(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalanceFrom($account, 200, Carbon::today()->subYears(2));

        // Origination 6 months ago, 12 monthly payments → first 6 due dates
        // are in the past and must be flagged late on creation.
        $origination = Carbon::today()->subMonths(6);
        $line = $this->drawService->open($account, 120.0, 12, 'monthly', null, $origination);

        $rows = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->get();

        $this->assertCount(12, $rows);

        $late = $rows->where('status', CreditLinePayment::STATUS_LATE);
        $scheduled = $rows->where('status', CreditLinePayment::STATUS_SCHEDULED);

        // Every late row is in the past; every scheduled row is in the future.
        $this->assertTrue($late->isNotEmpty(), 'expected some late rows');
        foreach ($late as $row) {
            $this->assertTrue(Carbon::parse($row->due_date)->lt(Carbon::today()));
        }
        foreach ($scheduled as $row) {
            $this->assertTrue(Carbon::parse($row->due_date)->gte(Carbon::today()));
        }
    }

    public function test_same_day_draw_leaves_rows_scheduled(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalanceFrom($account, 200, Carbon::today()->subYears(1));

        $line = $this->drawService->open($account, 60.0, 6, 'monthly', null, Carbon::today());

        $statuses = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->pluck('status')
            ->unique()
            ->values()
            ->all();

        $this->assertEquals([CreditLinePayment::STATUS_SCHEDULED], $statuses);
    }

    public function test_late_detector_sweeps_overdue_scheduled_rows(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalanceFrom($account, 200, Carbon::today()->subYears(1));

        $line = $this->drawService->open($account, 60.0, 6, 'monthly', null, Carbon::today());

        // Force the earliest row to be overdue, then sweep.
        $first = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')->first();
        $first->due_date = Carbon::today()->subDay()->toDateString();
        $first->save();

        $count = (new LateDetector())->detectForLine($line);

        $this->assertEquals(1, $count);
        $this->assertEquals(
            CreditLinePayment::STATUS_LATE,
            $first->fresh()->status
        );
    }

    public function test_repay_row_marks_specific_row_paid(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalanceFrom($account, 200, Carbon::today()->subYears(1));

        $line = $this->drawService->open($account, 120.0, 12, 'monthly', null, Carbon::today());

        $rows = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')->get();
        $target = $rows[2]; // pay the 3rd installment directly

        $tran = $this->repayService->repayRow($target, (float) $target->shares_due, Carbon::today());

        $this->assertEquals(TransactionExt::TYPE_REPAY, $tran->type);
        $target->refresh();
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $target->status);
        $this->assertEquals($tran->id, $target->paid_transaction_id);

        // Earlier rows untouched (we targeted row #3, not oldest-first).
        $this->assertEquals(CreditLinePayment::STATUS_SCHEDULED, $rows[0]->fresh()->status);
        $this->assertEquals(CreditLinePayment::STATUS_SCHEDULED, $rows[1]->fresh()->status);
    }

    public function test_repay_row_short_payment_marks_partial(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalanceFrom($account, 200, Carbon::today()->subYears(1));

        $line = $this->drawService->open($account, 120.0, 12, 'monthly', null, Carbon::today());
        $row  = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')->first();

        $half = round($row->shares_due / 2, 4);
        $this->repayService->repayRow($row, $half, Carbon::today());

        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $row->fresh()->status);
    }

    public function test_repay_row_overflow_cascades_to_next_rows(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalanceFrom($account, 200, Carbon::today()->subYears(1));

        $line = $this->drawService->open($account, 120.0, 12, 'monthly', null, Carbon::today());
        $rows = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')->get();

        // Pay 2.5x one installment against row #1 → rows 1 & 2 fully paid.
        $payment = round($rows[0]->shares_due * 2.5, 4);
        $this->repayService->repayRow($rows[0], $payment, Carbon::today());

        $this->assertEquals(CreditLinePayment::STATUS_PAID, $rows[0]->fresh()->status);
        $this->assertEquals(CreditLinePayment::STATUS_PAID, $rows[1]->fresh()->status);
    }

    public function test_trajectory_overdue_backlog_counts_partial_row_remainder(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalanceFrom($account, 400, Carbon::today()->subYears(2));

        // Originate 4 months ago: the earliest monthly rows are past-due and
        // flagged LATE on open; later rows are future SCHEDULED rows.
        $origination = Carbon::today()->subMonths(4);
        $line = $this->drawService->open($account, 120.0, 12, 'monthly', null, $origination);

        $rows = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')->get();

        $lateRows = $rows->where('status', CreditLinePayment::STATUS_LATE)->values();
        $this->assertGreaterThanOrEqual(
            2,
            $lateRows->count(),
            'need at least two past-due rows to exercise partial + full backlog'
        );

        // Partially settle the oldest past-due row: pay 40% of what it owes.
        $partialRow = $lateRows[0];
        $due  = (float) $partialRow->shares_due;
        $paid = round($due * 0.4, 4);
        $this->repayService->repayRow($partialRow, $paid, Carbon::today());
        $this->assertEquals(CreditLinePayment::STATUS_PARTIAL, $partialRow->fresh()->status);

        $trajectory = (new TrajectoryBuilder())->build($line->fresh());

        // Every past-due installment is still behind: the partial row counts
        // too (it's underpaid), so the tally is the full late-row count — the
        // partial row is not silently dropped.
        $this->assertEquals($lateRows->count(), $trajectory['overdue_installments']);

        // Backlog = the *unpaid remainder* of the partial row plus the whole
        // shares_due of every still-untouched LATE row — the real diff, with
        // the partial payment netted out (not the full first installment).
        $remainingLate = $lateRows->slice(1)->sum(fn ($r) => (float) $r->shares_due);
        $expectedBacklog = round(($due - $paid) + $remainingLate, 4);
        $this->assertEqualsWithDelta(
            $expectedBacklog,
            $trajectory['overdue_shares'],
            0.0001,
            'overdue backlog must net out the partial payment, not count the full installment'
        );
    }

    /**
     * Seed an OWN balance whose start_dt is in the past, so a backdated
     * draw's as-of draw-cap check (availableToBorrow at origination_date)
     * sees the shares.
     */
    private function seedOwnBalanceFrom($account, float $shares, Carbon $startDate): void
    {
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
}
