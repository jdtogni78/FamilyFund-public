<?php

namespace Tests\Unit\Services\CreditLine\Reporting;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Reporting\TrajectoryBuilder;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

class TrajectoryBuilderTest extends TestCase
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

    private function makeRepayService(): RepayService
    {
        return new RepayService(new OutstandingCalculator());
    }

    private function openLine($account, float $principal, int $termMonths): AccountCreditLine
    {
        $draw = new DrawService(new AmortizationScheduleBuilder(), new OutstandingCalculator());
        return $draw->open($account, $principal, $termMonths, 'monthly');
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

    private function makeLine(float $principal = 120.0, int $termMonths = 12, string $originationDate = '2026-01-01'): AccountCreditLine
    {
        return AccountCreditLine::create([
            'account_id'         => $this->factory->userAccount->id,
            'principal_shares'   => $principal,
            'outstanding_shares' => $principal,
            'term_months'        => $termMonths,
            'origination_date'   => $originationDate,
            'maturity_date'      => Carbon::parse($originationDate)->addMonths($termMonths)->toDateString(),
            'payment_frequency'  => 'monthly',
            'status'             => 'active',
        ]);
    }

    public function test_no_adjustments_returns_only_original_and_current(): void
    {
        $line = $this->makeLine(120.0, 12);

        // Create 12 monthly scheduled rows totaling 120 shares.
        for ($i = 0; $i < 12; $i++) {
            CreditLinePayment::create([
                'account_credit_line_id' => $line->id,
                'due_date'               => Carbon::parse('2026-01-01')->addMonths($i)->toDateString(),
                'shares_due'             => 10.0,
                'status'                 => CreditLinePayment::STATUS_SCHEDULED,
            ]);
        }

        $traj = $this->builder->build($line);

        $this->assertCount(12, $traj['original_plan']);
        $this->assertEmpty($traj['historical_plans']);
        $this->assertSame($traj['original_plan'], $traj['current_plan']);
        $this->assertEmpty($traj['actual_repayments']);
        $this->assertNull($traj['projected_payoff_date']);
        $this->assertNotNull($traj['planned_payoff_date']);
    }

    public function test_actual_repayments_accumulate_and_project(): void
    {
        $line = $this->makeLine(100.0, 10);
        $account = $this->factory->userAccount;

        // 3 repayments of 5 shares each, 30 days apart.
        $start = Carbon::parse('2026-01-15');
        for ($i = 0; $i < 3; $i++) {
            $tran = Transaction::factory()->for($account, 'account')->create([
                'type'      => TransactionExt::TYPE_REPAY,
                'status'    => TransactionExt::STATUS_CLEARED,
                'value'     => 50,
                'shares'    => 5,
                'reversed'  => false,
                'timestamp' => $start->copy()->addDays(30 * $i),
                'account_credit_line_id' => $line->id,
            ]);
            CreditLinePayment::create([
                'account_credit_line_id' => $line->id,
                'due_date'               => $start->copy()->addDays(30 * $i)->toDateString(),
                'shares_due'             => 5,
                'status'                 => CreditLinePayment::STATUS_PAID,
                'paid_transaction_id'    => $tran->id,
            ]);
        }
        $line->update(['outstanding_shares' => 85.0]);

        $traj = $this->builder->build($line);

        $this->assertCount(3, $traj['actual_repayments']);
        $this->assertSame(15.0, $traj['actual_repayments'][2]['cumulative_shares']);
        $this->assertNotNull($traj['projected_payoff_date']);
    }

    public function test_actual_repayments_include_only_confirmed_schedule_payments(): void
    {
        $line = $this->makeLine(100.0, 10);
        $account = $this->factory->userAccount;

        $confirmed = Transaction::factory()->for($account, 'account')->create([
            'type'      => TransactionExt::TYPE_REPAY,
            'status'    => TransactionExt::STATUS_CLEARED,
            'value'     => 50,
            'shares'    => 5,
            'reversed'  => false,
            'timestamp' => Carbon::parse('2026-01-15'),
            'account_credit_line_id' => $line->id,
        ]);

        Transaction::factory()->for($account, 'account')->create([
            'type'      => TransactionExt::TYPE_REPAY,
            'status'    => TransactionExt::STATUS_CLEARED,
            'value'     => 70,
            'shares'    => 7,
            'reversed'  => false,
            'timestamp' => Carbon::parse('2026-02-15'),
            'account_credit_line_id' => $line->id,
        ]);

        CreditLinePayment::create([
            'account_credit_line_id' => $line->id,
            'due_date'               => '2026-01-15',
            'shares_due'             => 5,
            'status'                 => CreditLinePayment::STATUS_PAID,
            'paid_transaction_id'    => $confirmed->id,
        ]);

        $traj = $this->builder->build($line);

        $this->assertSame([
            ['date' => '2026-01-15', 'cumulative_shares' => 5.0],
        ], $traj['actual_repayments']);
    }

    // -----------------------------------------------------------------
    // Issue #4: overdue backlog must reflect the full allocation ledger,
    // not just the latest representative transaction.
    // -----------------------------------------------------------------

    /**
     * Two partial REPs land on the same past-due row. The trajectory's
     * `overdue_shares` must equal `shares_due − (sum of allocations)`,
     * not `shares_due − (latest tx.shares)`.
     */
    public function test_overdue_shares_sums_all_partial_allocations_not_just_latest(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        // One row of 10 shares.
        $line = $this->openLine($account, 10.0, 1);

        // Force the row's due_date into the past so it qualifies as overdue.
        $row = CreditLinePayment::where('account_credit_line_id', $line->id)->firstOrFail();
        $row->due_date = Carbon::today()->subDays(30)->toDateString();
        $row->save();

        // Two partial repayments of 3 shares each (total 6 on a 10-share row).
        $repay = $this->makeRepayService();
        $repay->repayRow($row->refresh(), 3.0);
        $repay->repayRow($row->refresh(), 3.0);

        $row->refresh();
        $this->assertSame(CreditLinePayment::STATUS_PARTIAL, $row->status);

        $traj = $this->builder->build($line);

        // True remaining = 10 − (3+3) = 4. The pre-fix implementation read
        // only the *latest* partial tx's shares (3) via paid_transaction_id
        // and reported remaining = 10 − 3 = 7.
        $this->assertEqualsWithDelta(4.0, $traj['overdue_shares'], 1e-4);
        $this->assertSame(1, $traj['overdue_installments']);
    }

    /**
     * A single overpayment cascades from one row onto a past-due partial
     * row. The overflow's *allocation* (not the whole tx) is what should
     * count toward closing the overdue gap.
     */
    public function test_overdue_shares_uses_per_row_allocation_when_overflow_spans_multiple_rows(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 200.0);

        // Two rows of 10 each, both past-due.
        $line = $this->openLine($account, 20.0, 2);
        $rows = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')->get();
        foreach ($rows as $i => $r) {
            $r->due_date = Carbon::today()->subDays(60 - $i * 10)->toDateString();
            $r->save();
        }
        [$r1, $r2] = [$rows[0], $rows[1]];

        $repay = $this->makeRepayService();

        // Seed a partial on row 1 so it's PARTIAL & past-due.
        $repay->repayRow($r1->refresh(), 4.0);

        // Big payment on row 2 covers it (10) and overflows 8 back onto r1
        // (cascading rule: overflow goes to oldest open row first).
        $repay->repayRow($r2->refresh(), 18.0);

        $r1->refresh();
        $this->assertSame(CreditLinePayment::STATUS_PAID, $r1->status);

        $traj = $this->builder->build($line);

        // Row 1 is now fully covered, row 2 is fully covered → no backlog.
        $this->assertEqualsWithDelta(0.0, $traj['overdue_shares'], 1e-4);
        $this->assertSame(0, $traj['overdue_installments']);
    }

    public function test_single_repayment_no_projection(): void
    {
        $line = $this->makeLine(100.0, 12);
        $tran = Transaction::factory()->for($this->factory->userAccount, 'account')->create([
            'type'      => TransactionExt::TYPE_REPAY,
            'status'    => TransactionExt::STATUS_CLEARED,
            'value'     => 100,
            'shares'    => 10,
            'reversed'  => false,
            'timestamp' => Carbon::parse('2026-02-01'),
            'account_credit_line_id' => $line->id,
        ]);
        CreditLinePayment::create([
            'account_credit_line_id' => $line->id,
            'due_date'               => '2026-02-01',
            'shares_due'             => 10,
            'status'                 => CreditLinePayment::STATUS_PAID,
            'paid_transaction_id'    => $tran->id,
        ]);

        $traj = $this->builder->build($line);
        $this->assertNull($traj['projected_payoff_date']);
    }
}
