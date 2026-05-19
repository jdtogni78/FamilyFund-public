<?php

namespace Tests\Feature;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\Asset;
use App\Models\CreditLineAdjustment;
use App\Models\CreditLinePayment;
use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * The repair command is a thin, idempotent delegate over
 * PaymentGenerationRepairer: dry-run by default, --apply to persist, and a
 * strict no-op on a second run.
 */
class RepairCancelledPreEffectivePaymentsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );
        Carbon::setTestNow(Carbon::parse('2026-01-01'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_dry_run_then_apply_then_idempotent_no_op(): void
    {
        $factory = new DataFactory();
        $factory->createFund(1000, 1000, '2022-01-01');
        $factory->createUser();

        Carbon::setTestNow('2026-01-01 00:00:00');
        $line = AccountCreditLine::create([
            'account_id'         => $factory->userAccount->id,
            'principal_shares'   => 60.0,
            'outstanding_shares' => 60.0,
            'term_months'        => 12,
            'origination_date'   => '2026-01-01',
            'maturity_date'      => '2027-01-01',
            'payment_frequency'  => AccountCreditLineExt::FREQUENCY_MONTHLY,
            'status'             => AccountCreditLineExt::STATUS_ACTIVE,
            'descr'              => 'cmd test',
        ]);
        Transaction::factory()->for($factory->userAccount, 'account')->create([
            'type'                     => TransactionExt::TYPE_BORROW,
            'status'                   => TransactionExt::STATUS_CLEARED,
            'value'                    => 600,
            'shares'                   => 60.0,
            'account_credit_line_id'   => $line->id,
            'credit_line_match_status' => null,
            'reversed'                 => false,
            'timestamp'                => now(),
        ]);
        $builder = new AmortizationScheduleBuilder();
        $origination = $builder->build($line, 60.0, Carbon::parse('2026-01-01'));
        $originationIds = array_map(fn ($p) => $p->id, $origination);

        // Legacy forward-dated scar: an adjustment, all origination rows
        // cancelled (the old bug), an unstamped fresh schedule.
        Carbon::setTestNow('2026-09-15 12:00:00');
        CreditLineAdjustment::create([
            'account_credit_line_id'           => $line->id,
            'adjusted_at'                      => now(),
            'effective_date'                   => '2026-10-01',
            'adjusted_by_user_id'              => null,
            'outstanding_shares_at_adjustment' => 60.0,
            'old_term_months'                  => 12,
            'new_term_months'                  => 12,
            'old_payment_frequency'            => 'monthly',
            'new_payment_frequency'            => 'monthly',
            'old_maturity_date'                => '2027-01-01',
            'new_maturity_date'                => '2027-10-01',
            'old_planned_payoff_date'          => '2027-01-01',
            'new_planned_payoff_date'          => '2027-10-01',
            'reason'                           => 'legacy',
        ]);
        CreditLinePayment::whereIn('id', $originationIds)
            ->update(['status' => CreditLinePayment::STATUS_CANCELLED]);
        $builder->build($line, 60.0, Carbon::parse('2026-10-01'));
        Carbon::setTestNow('2026-01-01');

        // Dry run: reports work, mutates nothing.
        $this->artisan('credit-lines:repair-cancelled-pre-effective')
            ->expectsOutputToContain('DRY RUN')
            ->assertExitCode(0);
        $this->assertSame(
            12,
            CreditLinePayment::whereIn('id', $originationIds)
                ->where('status', CreditLinePayment::STATUS_CANCELLED)
                ->count(),
            'dry run must not mutate'
        );

        // Apply: restores + backfills.
        $this->artisan('credit-lines:repair-cancelled-pre-effective --apply')
            ->assertExitCode(0);
        $this->assertSame(
            8,
            CreditLinePayment::whereIn('id', $originationIds)
                ->where('status', CreditLinePayment::STATUS_SCHEDULED)
                ->count()
        );

        // Idempotent: second apply is a strict no-op.
        $this->artisan('credit-lines:repair-cancelled-pre-effective --apply')
            ->expectsOutputToContain('already consistent')
            ->assertExitCode(0);
    }
}
