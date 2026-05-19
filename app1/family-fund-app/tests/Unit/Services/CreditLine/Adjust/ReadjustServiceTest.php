<?php

namespace Tests\Unit\Services\CreditLine\Adjust;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\Asset;
use App\Models\CreditLineAdjustment;
use App\Models\CreditLinePayment;
use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Services\CreditLine\Adjust\AdjustmentHistoryBuilder;
use App\Services\CreditLine\Adjust\ReadjustService;
use App\Services\CreditLine\Adjust\ScheduleSnapshotBuilder;
use App\Services\CreditLine\Exceptions\NoChangeException;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for ReadjustService, AdjustmentHistoryBuilder, ScheduleSnapshotBuilder.
 *
 * Covers UC-09, UC-10, UC-40, UC-41, UC-43.
 */
class ReadjustServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private ReadjustService $service;
    private AdjustmentHistoryBuilder $historyBuilder;
    private ScheduleSnapshotBuilder $snapshotBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        // CASH asset required by DataFactory::createFund()
        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        $outstandingCalc  = new OutstandingCalculator();
        $scheduleBuilder  = new AmortizationScheduleBuilder();

        $this->service         = new ReadjustService($outstandingCalc, $scheduleBuilder);
        $this->historyBuilder  = new AdjustmentHistoryBuilder();
        $this->snapshotBuilder = new ScheduleSnapshotBuilder();

        // Freeze "today" to the default origination date so a readjust with no
        // explicit effective date (effective = today) lands on/before every
        // installment in the initial schedule. Otherwise the wall clock would
        // make the cancelled-vs-preserved split (rows due >= effective date)
        // non-deterministic. Tests that exercise effective dating set their own
        // Carbon::setTestNow and/or pass an explicit effective date.
        Carbon::setTestNow(Carbon::parse('2026-01-01'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCreditLine(
        float $principalShares,
        int $termMonths = 12,
        string $frequency = AccountCreditLineExt::FREQUENCY_MONTHLY,
        string $originationDate = '2026-01-01'
    ): AccountCreditLine {
        $account = $this->factory->userAccount;

        return AccountCreditLine::create([
            'account_id'        => $account->id,
            'principal_shares'  => $principalShares,
            'outstanding_shares'=> $principalShares,
            'term_months'       => $termMonths,
            'origination_date'  => $originationDate,
            'maturity_date'     => Carbon::parse($originationDate)->addMonths($termMonths)->toDateString(),
            'payment_frequency' => $frequency,
            'status'            => AccountCreditLineExt::STATUS_ACTIVE,
            'descr'             => 'Test line',
        ]);
    }

    /**
     * Create a BOR transaction linked to a credit line and a balance row.
     */
    private function createBorTransaction(AccountCreditLine $line, float $shares): TransactionExt
    {
        $account = $this->factory->userAccount;

        $tran = Transaction::factory()->for($account, 'account')->create([
            'type'                   => TransactionExt::TYPE_BORROW,
            'status'                 => TransactionExt::STATUS_CLEARED,
            'value'                  => $shares * 10,
            'shares'                 => $shares,
            'account_credit_line_id' => $line->id,
            'credit_line_match_status' => null,
            'reversed'               => false,
            'timestamp'              => now(),
        ]);

        return TransactionExt::find($tran->id);
    }

    /**
     * Create a REP transaction linked to a credit line.
     */
    private function createRepTransaction(AccountCreditLine $line, float $shares): TransactionExt
    {
        $account = $this->factory->userAccount;

        $tran = Transaction::factory()->for($account, 'account')->create([
            'type'                   => TransactionExt::TYPE_REPAY,
            'status'                 => TransactionExt::STATUS_CLEARED,
            'value'                  => $shares * 10,
            'shares'                 => $shares,
            'account_credit_line_id' => $line->id,
            'credit_line_match_status' => TransactionExt::MATCH_STATUS_AUTO_MATCHED,
            'reversed'               => false,
            'timestamp'              => now(),
        ]);

        return TransactionExt::find($tran->id);
    }

    /**
     * Build an initial schedule for a line using the AmortizationScheduleBuilder.
     */
    private function buildInitialSchedule(AccountCreditLine $line): array
    {
        $builder = new AmortizationScheduleBuilder();
        return $builder->build($line, $line->outstanding_shares, Carbon::parse($line->origination_date));
    }

    // -------------------------------------------------------------------------
    // ReadjustService tests
    // -------------------------------------------------------------------------

    /**
     * UC-09: Extend term from 12 to 24 months on a fresh 100-share line.
     * Expected: old 12 rows cancelled, 24 new rows created, sum = 100 exactly.
     * Audit row written with old_term_months=12, new_term_months=24.
     */
    public function test_extend_term_generates_new_schedule_and_cancels_old()
    {
        $line = $this->makeCreditLine(100.0, 12);
        $this->createBorTransaction($line, 100.0);
        $initialPayments = $this->buildInitialSchedule($line);
        $this->assertCount(12, $initialPayments);

        $adjustment = $this->service->readjust($line, 24, null, null, 'need more time');

        // Audit row assertions.
        $this->assertInstanceOf(CreditLineAdjustment::class, $adjustment);
        $this->assertEquals(12, $adjustment->old_term_months);
        $this->assertEquals(24, $adjustment->new_term_months);
        $this->assertNull($adjustment->adjusted_by_user_id);
        $this->assertEquals('need more time', $adjustment->reason);

        // Old rows cancelled.
        $cancelled = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_CANCELLED)
            ->count();
        $this->assertEquals(12, $cancelled);

        // New rows scheduled.
        $scheduled = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->get();
        $this->assertCount(24, $scheduled);

        // Sum of new rows = 100 exactly.
        $sum = $scheduled->sum('shares_due');
        $this->assertEquals(100.0, round($sum, 4));
    }

    /**
     * UC-10: Shorten term from 24 to 12 months — same code path.
     */
    public function test_shorten_term_generates_new_schedule_and_cancels_old()
    {
        $line = $this->makeCreditLine(60.0, 24);
        $this->createBorTransaction($line, 60.0);
        $this->buildInitialSchedule($line);

        $adjustment = $this->service->readjust($line, 12, null, null);

        $this->assertEquals(24, $adjustment->old_term_months);
        $this->assertEquals(12, $adjustment->new_term_months);

        $scheduled = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->get();
        $this->assertCount(12, $scheduled);
        $this->assertEquals(60.0, round($scheduled->sum('shares_due'), 4));
    }

    /**
     * Partial repayment: outstanding = 70, new schedule sums to 70.
     */
    public function test_readjust_after_partial_repayment_sums_to_outstanding()
    {
        $line = $this->makeCreditLine(100.0, 12);
        $this->createBorTransaction($line, 100.0);
        $this->createRepTransaction($line, 30.0);
        $this->buildInitialSchedule($line);

        $adjustment = $this->service->readjust($line, 24, null, null);

        $scheduled = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->get();

        $this->assertEquals(70.0, round($scheduled->sum('shares_due'), 4));
        $this->assertEquals(70.0, $adjustment->outstanding_shares_at_adjustment);
    }

    /**
     * UC-43: No-change guard — throws NoChangeException without DB writes.
     */
    public function test_no_change_throws_exception_and_writes_nothing()
    {
        $line = $this->makeCreditLine(100.0, 12);
        $this->createBorTransaction($line, 100.0);
        $this->buildInitialSchedule($line);

        $countBefore = CreditLineAdjustment::where('account_credit_line_id', $line->id)->count();

        $this->expectException(NoChangeException::class);

        $this->service->readjust($line, 12, AccountCreditLineExt::FREQUENCY_MONTHLY, null);

        // Should not reach here.
        $countAfter = CreditLineAdjustment::where('account_credit_line_id', $line->id)->count();
        $this->assertEquals($countBefore, $countAfter);
    }

    /**
     * Readjust with adminUser=NULL (system-initiated) → adjusted_by_user_id is NULL.
     */
    public function test_system_readjust_has_null_adjusted_by()
    {
        $line = $this->makeCreditLine(50.0, 12);
        $this->createBorTransaction($line, 50.0);
        $this->buildInitialSchedule($line);

        $adjustment = $this->service->readjust($line, 18, null, null);

        $this->assertNull($adjustment->adjusted_by_user_id);
    }

    /**
     * Frequency change only (term unchanged) still triggers an adjustment.
     */
    public function test_frequency_only_change_creates_adjustment()
    {
        $line = $this->makeCreditLine(120.0, 12, AccountCreditLineExt::FREQUENCY_MONTHLY);
        $this->createBorTransaction($line, 120.0);
        $this->buildInitialSchedule($line);

        $adjustment = $this->service->readjust($line, null, AccountCreditLineExt::FREQUENCY_QUARTERLY, null);

        $this->assertEquals(AccountCreditLineExt::FREQUENCY_MONTHLY, $adjustment->old_payment_frequency);
        $this->assertEquals(AccountCreditLineExt::FREQUENCY_QUARTERLY, $adjustment->new_payment_frequency);

        // 12 months / 3 months-per-period = 4 payments
        $scheduled = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->get();
        $this->assertCount(4, $scheduled);
        $this->assertEquals(120.0, round($scheduled->sum('shares_due'), 4));
    }

    /**
     * Old scheduled rows are preserved (not deleted) after cancel.
     */
    public function test_old_scheduled_rows_are_preserved_as_cancelled()
    {
        $line = $this->makeCreditLine(36.0, 12);
        $this->createBorTransaction($line, 36.0);
        $this->buildInitialSchedule($line);

        $totalBefore = CreditLinePayment::where('account_credit_line_id', $line->id)->count();

        $this->service->readjust($line, 24, null, null);

        $totalAfter = CreditLinePayment::where('account_credit_line_id', $line->id)->count();

        // 12 cancelled + 24 new = 36 total rows
        $this->assertEquals($totalBefore + 24, $totalAfter);

        $cancelledCount = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_CANCELLED)
            ->count();
        $this->assertEquals(12, $cancelledCount);
    }

    /**
     * Maturity date on the line is updated to today + new term months.
     */
    public function test_maturity_date_updated_on_line_after_readjust()
    {
        $line = $this->makeCreditLine(48.0, 12);
        $this->createBorTransaction($line, 48.0);
        $this->buildInitialSchedule($line);

        Carbon::setTestNow('2026-05-13');
        $this->service->readjust($line, 24, null, null);
        Carbon::setTestNow(null);

        $line->refresh();
        $this->assertEquals('2028-05-13', $line->maturity_date->toDateString());
    }

    /**
     * An explicit effective (start) date anchors the new schedule and maturity
     * to that date instead of "today", and is recorded on the audit row.
     */
    public function test_readjust_honors_explicit_effective_date()
    {
        $line = $this->makeCreditLine(120.0, 12, AccountCreditLineExt::FREQUENCY_MONTHLY);
        $this->createBorTransaction($line, 120.0);
        $this->buildInitialSchedule($line);

        Carbon::setTestNow('2026-05-17');
        $start = Carbon::parse('2026-08-01');
        $adjustment = $this->service->readjust($line, 12, null, null, 'start later', $start);
        Carbon::setTestNow(null);

        // Audit row records the chosen start date.
        $this->assertEquals('2026-08-01', $adjustment->effective_date->toDateString());

        // Maturity anchored to start + term, not today + term.
        $line->refresh();
        $this->assertEquals('2027-08-01', $line->maturity_date->toDateString());

        // Forward-dated reschedule must NOT cancel installments due before the
        // effective date — the old plan stays in force until the new one
        // starts. Initial schedule (origination 2026-01-01, 12 monthly) runs
        // 2026-02-01 … 2027-01-01.
        $preEffectiveStillScheduled = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->whereDate('due_date', '<', '2026-08-01')
            ->count();
        // 2026-02-01 … 2026-07-01 = 6 rows preserved in force.
        $this->assertEquals(6, $preEffectiveStillScheduled);

        // Old rows due on/after the effective date are superseded → cancelled.
        $cancelled = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_CANCELLED)
            ->get();
        $this->assertCount(6, $cancelled); // 2026-08-01 … 2027-01-01
        foreach ($cancelled as $c) {
            $this->assertTrue(Carbon::parse($c->due_date)->gte(Carbon::parse('2026-08-01')));
        }

        // The new schedule's first payment falls one month after the start
        // date — i.e. the earliest scheduled row on/after the effective date.
        $firstNew = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->whereDate('due_date', '>=', '2026-08-01')
            ->orderBy('due_date')
            ->first();
        $this->assertEquals('2026-09-01', Carbon::parse($firstNew->due_date)->toDateString());
    }

    /**
     * Supplying only an effective date (term + frequency unchanged) is a valid
     * reschedule — it must NOT throw NoChangeException. Forward-dating pushes
     * the plan out from the effective date: installments due before it stay in
     * force (the borrower still owes them under the old plan until the new one
     * starts); only rows due on/after it are superseded and cancelled.
     *
     * Regression: previously every old row was cancelled regardless of due
     * date, leaving the pre-effective-date window with no obligation at all
     * (the "gap" visible on the payoff-trajectory chart).
     */
    public function test_redate_only_preserves_pre_effective_installments_in_force()
    {
        // Origination 2026-01-01, 12 monthly → due 2026-02-01 … 2027-01-01.
        $line = $this->makeCreditLine(60.0, 12, AccountCreditLineExt::FREQUENCY_MONTHLY);
        $this->createBorTransaction($line, 60.0);
        $this->buildInitialSchedule($line);

        $adjustment = $this->service->readjust(
            $line,
            null,
            null,
            null,
            'push the whole plan out',
            Carbon::parse('2026-10-01')
        );

        $this->assertEquals(12, $adjustment->old_term_months);
        $this->assertEquals(12, $adjustment->new_term_months);
        $this->assertEquals('2026-10-01', $adjustment->effective_date->toDateString());

        // Old rows due BEFORE the effective date stay scheduled & in force:
        // 2026-02-01 … 2026-09-01 = 8 rows.
        $preEffective = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->whereDate('due_date', '<', '2026-10-01')
            ->get();
        $this->assertCount(8, $preEffective);

        // Old rows due ON/AFTER the effective date are superseded → cancelled:
        // 2026-10-01 … 2027-01-01 = 4 rows. Preserved (not deleted) for history.
        $cancelled = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_CANCELLED)
            ->get();
        $this->assertCount(4, $cancelled);
        foreach ($cancelled as $c) {
            $this->assertTrue(Carbon::parse($c->due_date)->gte(Carbon::parse('2026-10-01')));
        }

        // Fresh schedule (12 monthly) anchored to the effective date, all due
        // strictly after it (first = 2026-11-01).
        $newRows = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->whereDate('due_date', '>=', '2026-10-01')
            ->orderBy('due_date')
            ->get();
        $this->assertCount(12, $newRows);
        $this->assertEquals('2026-11-01', Carbon::parse($newRows->first()->due_date)->toDateString());
        $this->assertEquals(60.0, round($newRows->sum('shares_due'), 4));

        // The bug was a multi-month void in the pre-effective window because
        // every old row got cancelled. Assert that window is now an unbroken
        // monthly run: 2026-02-01 … 2026-09-01, 8 consecutive months.
        $preEffectiveDates = $preEffective
            ->pluck('due_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->sort()
            ->values()
            ->all();
        $this->assertEquals([
            '2026-02-01', '2026-03-01', '2026-04-01', '2026-05-01',
            '2026-06-01', '2026-07-01', '2026-08-01', '2026-09-01',
        ], $preEffectiveDates);
    }

    // -------------------------------------------------------------------------
    // Generation / supersession model
    // -------------------------------------------------------------------------

    /**
     * Generation contract: every payment row is stamped with the adjustment
     * that generated it. The fresh schedule rows carry the new adjustment's
     * id; the superseded origination rows keep credit_line_adjustment_id = NULL
     * (NULL == the origination generation). Nothing is deleted.
     */
    public function test_readjust_stamps_new_rows_and_leaves_origination_generation_null()
    {
        $line = $this->makeCreditLine(100.0, 12);
        $this->createBorTransaction($line, 100.0);
        $originationRows = $this->buildInitialSchedule($line);
        $originationIds  = array_map(fn ($p) => $p->id, $originationRows);

        $adjustment = $this->service->readjust($line, 24, null, null, 'extend');

        // Origination rows: still present (never deleted), cancelled, NULL stamp.
        $origination = CreditLinePayment::whereIn('id', $originationIds)->get();
        $this->assertCount(12, $origination);
        foreach ($origination as $row) {
            $this->assertSame(CreditLinePayment::STATUS_CANCELLED, $row->status);
            $this->assertNull(
                $row->credit_line_adjustment_id,
                'origination rows belong to the NULL (origination) generation'
            );
        }

        // Fresh schedule rows: all stamped with the owning adjustment.
        $fresh = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_SCHEDULED)
            ->get();
        $this->assertCount(24, $fresh);
        foreach ($fresh as $row) {
            $this->assertEquals(
                $adjustment->id,
                $row->credit_line_adjustment_id,
                'every fresh row is stamped with the adjustment that generated it'
            );
        }
    }

    /**
     * Back-to-back readjusts: each generation stays independently
     * attributable, so "which plan was in force on date D" is deterministic.
     *
     * Origination 2026-01-01, 12 monthly (NULL generation).
     * Adj#1 effective 2026-04-01 (term 24)  → generation = adj1->id.
     * Adj#2 effective 2026-09-01 (term 36)  → generation = adj2->id.
     *
     * Invariant asserted:
     *  - every row carries a generation stamp in {null, adj1, adj2};
     *  - exactly the generated counts are stamped to each adjustment;
     *  - no two live (non-cancelled) rows share a due_date;
     *  - every live row's due_date falls inside its generation's effective
     *    window [gen.effective_date, nextGen.effective_date).
     */
    public function test_back_to_back_readjusts_keep_each_generation_attributable()
    {
        $line = $this->makeCreditLine(120.0, 12, AccountCreditLineExt::FREQUENCY_MONTHLY);
        $this->createBorTransaction($line, 120.0);
        $this->buildInitialSchedule($line);

        Carbon::setTestNow('2026-03-15');
        $adj1 = $this->service->readjust($line, 24, null, null, 'first', Carbon::parse('2026-04-01'));

        Carbon::setTestNow('2026-08-15');
        $adj2 = $this->service->readjust($line->refresh(), 36, null, null, 'second', Carbon::parse('2026-09-01'));
        Carbon::setTestNow('2026-01-01');

        $all = CreditLinePayment::where('account_credit_line_id', $line->id)->get();

        // Nothing deleted: origination(12) + adj1(24) + adj2(36) = 72 rows.
        $this->assertCount(72, $all);

        // Every stamp is one of the three known generations.
        foreach ($all as $r) {
            $this->assertContains(
                $r->credit_line_adjustment_id,
                [null, $adj1->id, $adj2->id],
                "row {$r->id} has an unknown generation stamp"
            );
        }

        // Exactly the generated counts are attributed to each adjustment.
        $this->assertSame(12, $all->whereNull('credit_line_adjustment_id')->count());
        $this->assertSame(24, $all->where('credit_line_adjustment_id', $adj1->id)->count());
        $this->assertSame(36, $all->where('credit_line_adjustment_id', $adj2->id)->count());

        // Effective windows per generation.
        $windows = [
            'orig' => ['from' => Carbon::parse('2026-01-01'), 'to' => Carbon::parse($adj1->effective_date)],
            'a1'   => ['from' => Carbon::parse($adj1->effective_date), 'to' => Carbon::parse($adj2->effective_date)],
            'a2'   => ['from' => Carbon::parse($adj2->effective_date), 'to' => null],
        ];

        $live = $all->whereNotIn('status', [CreditLinePayment::STATUS_CANCELLED]);

        // No two live rows share a due_date (no double obligation).
        $liveDates = $live->map(fn ($r) => Carbon::parse($r->due_date)->toDateString())->values()->all();
        $this->assertSame(
            count($liveDates),
            count(array_unique($liveDates)),
            'no two live rows may share a due_date'
        );

        // Every live row sits inside its own generation's effective window —
        // so the plan in force on any date is deterministically resolvable.
        foreach ($live as $r) {
            $key = $r->credit_line_adjustment_id === null ? 'orig'
                 : ($r->credit_line_adjustment_id === $adj1->id ? 'a1' : 'a2');
            $due = Carbon::parse($r->due_date);
            $this->assertTrue(
                $due->gte($windows[$key]['from']),
                "live row {$r->id} (gen {$key}) due {$due->toDateString()} precedes its generation window"
            );
            if ($windows[$key]['to'] !== null) {
                $this->assertTrue(
                    $due->lt($windows[$key]['to']),
                    "live row {$r->id} (gen {$key}) due {$due->toDateString()} leaks past its generation window"
                );
            }
        }
    }

    // -------------------------------------------------------------------------
    // AdjustmentHistoryBuilder tests (UC-40)
    // -------------------------------------------------------------------------

    /**
     * A line with no adjustments → 1 entry (origination only).
     */
    public function test_history_builder_returns_origination_only_when_no_adjustments()
    {
        $line = $this->makeCreditLine(100.0, 12);

        $entries = $this->historyBuilder->build($line);

        $this->assertCount(1, $entries);
        $this->assertEquals('origination', $entries[0]['kind']);
        $this->assertEquals(100.0, $entries[0]['data']['principal_shares']);
        $this->assertEquals(12, $entries[0]['data']['term_months']);
    }

    /**
     * N adjustments → N+1 entries (origination + N adjustments), newest-first.
     */
    public function test_history_builder_returns_n_plus_one_entries_newest_first()
    {
        $line = $this->makeCreditLine(100.0, 12);
        $this->createBorTransaction($line, 100.0);
        $this->buildInitialSchedule($line);

        // First readjustment.
        Carbon::setTestNow('2026-06-01');
        $this->service->readjust($line, 18, null, null, 'first adjust');

        // Second readjustment.
        Carbon::setTestNow('2026-09-01');
        $this->service->readjust($line, 24, null, null, 'second adjust');
        Carbon::setTestNow(null);

        $entries = $this->historyBuilder->build($line);

        // 2 adjustments + 1 origination = 3 entries.
        $this->assertCount(3, $entries);

        // Newest-first: most recent adjustment is first.
        $this->assertEquals('adjustment', $entries[0]['kind']);
        $this->assertEquals(24, $entries[0]['data']['new_term_months']);

        $this->assertEquals('adjustment', $entries[1]['kind']);
        $this->assertEquals(18, $entries[1]['data']['new_term_months']);

        // Origination is last.
        $this->assertEquals('origination', $entries[2]['kind']);
    }

    /**
     * Adjustment diff array lists changed field names correctly.
     */
    public function test_history_builder_diff_contains_changed_fields()
    {
        $line = $this->makeCreditLine(100.0, 12, AccountCreditLineExt::FREQUENCY_MONTHLY);
        $this->createBorTransaction($line, 100.0);
        $this->buildInitialSchedule($line);

        $this->service->readjust($line, 24, AccountCreditLineExt::FREQUENCY_QUARTERLY, null);

        $entries = $this->historyBuilder->build($line);
        $diff    = $entries[0]['data']['diff'];

        $this->assertContains('term_months', $diff);
        $this->assertContains('payment_frequency', $diff);
    }

    // -------------------------------------------------------------------------
    // ScheduleSnapshotBuilder tests (UC-41)
    // -------------------------------------------------------------------------

    /**
     * snapshotAt returns only rows created on or before the given date.
     */
    public function test_snapshot_builder_filters_by_created_at()
    {
        $line = $this->makeCreditLine(100.0, 12);
        $this->createBorTransaction($line, 100.0);

        // Simulate: initial schedule created at T=0.
        Carbon::setTestNow('2026-01-01 00:00:00');
        $this->buildInitialSchedule($line);

        // Simulate: readjust at T=1.
        Carbon::setTestNow('2026-06-01 00:00:00');
        $this->service->readjust($line, 24, null, null);
        Carbon::setTestNow(null);

        // Snapshot as of T=0 should return only the initial 12 rows.
        $snapshotBefore = $this->snapshotBuilder->snapshotAt($line, Carbon::parse('2026-01-01 23:59:59'));
        $this->assertCount(12, $snapshotBefore);

        // Snapshot as of T=1 should return all rows (12 + 24 = 36).
        $snapshotAfter = $this->snapshotBuilder->snapshotAt($line, Carbon::parse('2026-06-02 00:00:00'));
        $this->assertCount(36, $snapshotAfter);
    }

    /**
     * Snapshot rows are ordered by due_date ascending.
     */
    public function test_snapshot_builder_orders_by_due_date()
    {
        $line = $this->makeCreditLine(60.0, 6);
        $this->createBorTransaction($line, 60.0);
        $this->buildInitialSchedule($line);

        $snapshot = $this->snapshotBuilder->snapshotAt($line, Carbon::now()->addYear());

        $dueDates = $snapshot->pluck('due_date')->map(fn($d) => Carbon::parse($d))->values();
        for ($i = 1; $i < $dueDates->count(); $i++) {
            $this->assertTrue($dueDates[$i]->gte($dueDates[$i - 1]));
        }
    }
}
