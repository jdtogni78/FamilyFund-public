<?php

namespace Tests\Unit\Services\CreditLine\Reporting;

use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Services\CreditLine\Reporting\TrajectoryBuilder;
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
