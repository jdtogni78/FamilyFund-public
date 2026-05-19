<?php

namespace Tests\Unit\Services\CreditLine\Adjust;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\Asset;
use App\Models\CreditLineAdjustment;
use App\Models\CreditLinePayment;
use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Services\CreditLine\Adjust\PaymentGenerationRepairer;
use App\Services\CreditLine\Adjust\ReadjustService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * The repairer is the canonical, idempotent enforcer of the generation /
 * supersession invariant:
 *
 *  - every payment row carries credit_line_adjustment_id (NULL == origination);
 *  - a row is cancelled iff a later generation supersedes its slot;
 *  - rows wrongly cancelled before a forward-dated effective date are restored;
 *  - nothing is ever deleted; no live duplicate due_date is created.
 *
 * It backfills legacy rows (no generation stamp, pre-fix forward-dating bug)
 * and is a strict no-op on data already produced by the fixed ReadjustService.
 */
class PaymentGenerationRepairerTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private PaymentGenerationRepairer $repairer;
    private ReadjustService $service;

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

        $this->repairer = new PaymentGenerationRepairer();
        $this->service  = new ReadjustService(
            new OutstandingCalculator(),
            new AmortizationScheduleBuilder()
        );

        Carbon::setTestNow(Carbon::parse('2026-01-01'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

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

    private function createBorTransaction(AccountCreditLine $line, float $shares): void
    {
        Transaction::factory()->for($this->factory->userAccount, 'account')->create([
            'type'                     => TransactionExt::TYPE_BORROW,
            'status'                   => TransactionExt::STATUS_CLEARED,
            'value'                    => $shares * 10,
            'shares'                   => $shares,
            'account_credit_line_id'   => $line->id,
            'credit_line_match_status' => null,
            'reversed'                 => false,
            'timestamp'                => now(),
        ]);
    }

    /**
     * Reproduce the pre-fix legacy scar:
     *  - an origination schedule whose rows were ALL cancelled (the old step-4
     *    bug cancelled every scheduled row regardless of due date);
     *  - a forward-dated adjustment;
     *  - a fresh schedule whose rows were never generation-stamped.
     *
     * The repairer must restore the pre-effective origination rows, backfill
     * generation stamps, never delete, never double-bill a due_date, and be
     * fully idempotent on a second run.
     */
    public function test_repairs_legacy_scar_and_is_idempotent()
    {
        // Origination schedule written at T0.
        Carbon::setTestNow('2026-01-01 00:00:00');
        $line = $this->makeCreditLine(60.0, 12, AccountCreditLineExt::FREQUENCY_MONTHLY);
        $this->createBorTransaction($line, 60.0);
        $builder = new AmortizationScheduleBuilder();
        $origination = $builder->build($line, 60.0, Carbon::parse('2026-01-01'));
        $originationIds = array_map(fn ($p) => $p->id, $origination);
        // Origination runs 2026-02-01 … 2027-01-01.

        // Forward-dated readjust at T1, effective 2026-10-01.
        Carbon::setTestNow('2026-09-15 12:00:00');
        $adjustment = CreditLineAdjustment::create([
            'account_credit_line_id'           => $line->id,
            'adjusted_at'                      => now(),
            'effective_date'                   => '2026-10-01',
            'adjusted_by_user_id'              => null,
            'outstanding_shares_at_adjustment' => 60.0,
            'old_term_months'                  => 12,
            'new_term_months'                  => 12,
            'old_payment_frequency'            => 'monthly',
            'new_payment_frequency'            => 'monthly',
            'old_maturity_date'                => $line->maturity_date,
            'new_maturity_date'                => '2027-10-01',
            'old_planned_payoff_date'          => '2027-01-01',
            'new_planned_payoff_date'          => '2027-10-01',
            'reason'                           => 'legacy forward-date',
        ]);

        // OLD BUG: every origination row cancelled (even pre-effective ones).
        CreditLinePayment::whereIn('id', $originationIds)
            ->update(['status' => CreditLinePayment::STATUS_CANCELLED]);

        // Fresh schedule generated by the buggy code — never stamped.
        $fresh = $builder->build($line, 60.0, Carbon::parse('2026-10-01'));
        $freshIds = array_map(fn ($p) => $p->id, $fresh);
        // Fresh runs 2026-11-01 … 2027-10-01.

        Carbon::setTestNow('2026-01-01');

        // --- Dry run detects but does not mutate ---------------------------
        $preview = $this->repairer->repair(false);
        $this->assertGreaterThan(0, $preview['restored']);
        $this->assertGreaterThan(0, $preview['stamped']);
        $this->assertSame(
            12,
            CreditLinePayment::whereIn('id', $originationIds)
                ->where('status', CreditLinePayment::STATUS_CANCELLED)
                ->count(),
            'dry run must not mutate'
        );

        // --- Apply ---------------------------------------------------------
        $applied = $this->repairer->repair(true);

        // Pre-effective origination rows (due < 2026-10-01: Feb…Sep = 8)
        // restored to scheduled; the 4 due on/after stay cancelled.
        $origination = CreditLinePayment::whereIn('id', $originationIds)->get();
        $restored = $origination->where('status', CreditLinePayment::STATUS_SCHEDULED);
        $this->assertCount(8, $restored);
        foreach ($restored as $r) {
            $this->assertTrue(Carbon::parse($r->due_date)->lt(Carbon::parse('2026-10-01')));
            $this->assertNull($r->credit_line_adjustment_id, 'origination stays NULL generation');
        }
        $stillCancelled = $origination->where('status', CreditLinePayment::STATUS_CANCELLED);
        $this->assertCount(4, $stillCancelled);
        foreach ($stillCancelled as $r) {
            $this->assertTrue(Carbon::parse($r->due_date)->gte(Carbon::parse('2026-10-01')));
        }

        // Fresh rows backfilled with the owning adjustment's id.
        $freshRows = CreditLinePayment::whereIn('id', $freshIds)->get();
        $this->assertCount(12, $freshRows);
        foreach ($freshRows as $r) {
            $this->assertEquals($adjustment->id, $r->credit_line_adjustment_id);
        }

        // Nothing deleted.
        $this->assertSame(
            24,
            CreditLinePayment::where('account_credit_line_id', $line->id)->count()
        );

        // No two live rows share a due_date.
        $liveDates = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', '!=', CreditLinePayment::STATUS_CANCELLED)
            ->pluck('due_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();
        $this->assertSame(count($liveDates), count(array_unique($liveDates)));

        $this->assertSame($applied['restored'], $preview['restored']);
        $this->assertSame($applied['stamped'], $preview['stamped']);

        // --- Idempotent: second run is a strict no-op ----------------------
        $second = $this->repairer->repair(true);
        $this->assertSame(0, $second['restored']);
        $this->assertSame(0, $second['stamped']);
        $this->assertSame(0, $second['linesTouched']);
    }

    /**
     * Data produced by the fixed ReadjustService already satisfies the
     * invariant, so the repairer must be a strict no-op over it — proving the
     * service and the repairer agree on the generation model.
     */
    public function test_no_op_over_data_from_fixed_service()
    {
        $line = $this->makeCreditLine(120.0, 12, AccountCreditLineExt::FREQUENCY_MONTHLY);
        $this->createBorTransaction($line, 120.0);
        (new AmortizationScheduleBuilder())->build($line, 120.0, Carbon::parse('2026-01-01'));

        Carbon::setTestNow('2026-03-15');
        $this->service->readjust($line, 24, null, null, 'fwd', Carbon::parse('2026-04-01'));
        Carbon::setTestNow('2026-08-15');
        $this->service->readjust($line->refresh(), 36, null, null, 'fwd2', Carbon::parse('2026-09-01'));
        Carbon::setTestNow('2026-01-01');

        $result = $this->repairer->repair(true);

        $this->assertSame(0, $result['restored']);
        $this->assertSame(0, $result['stamped']);
        $this->assertSame(0, $result['linesTouched']);
    }
}
