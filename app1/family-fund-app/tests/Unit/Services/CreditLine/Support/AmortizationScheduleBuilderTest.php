<?php

namespace Tests\Unit\Services\CreditLine\Support;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Schedule-correctness audit (2026-05-19): computeNPayments() used
 * round($termMonths / period), so a term not divisible by the frequency
 * period (10mo quarterly, 14mo annual, 2mo quarterly) silently rounded the
 * payment count and the schedule's last due_date drifted off the line's
 * maturity_date (= fromDate + term_months).
 *
 * Invariants every schedule must hold, regardless of divisibility:
 *  - Σ shares_due == outstanding exactly (last row absorbs the remainder).
 *  - due_dates strictly increasing (monotonic series).
 *  - last due_date == maturity_date == fromDate + term_months.
 */
class AmortizationScheduleBuilderTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private AmortizationScheduleBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        $this->builder = new AmortizationScheduleBuilder();
    }

    private function makeLine(int $termMonths, string $frequency, Carbon $origination): AccountCreditLine
    {
        return AccountCreditLine::create([
            'account_id'         => $this->factory->userAccount->id,
            'nickname'           => 'Audit line',
            'principal_shares'   => 100.0,
            'outstanding_shares' => 100.0,
            'term_months'        => $termMonths,
            'origination_date'   => $origination->toDateString(),
            'maturity_date'      => $origination->copy()->addMonths($termMonths)->toDateString(),
            'payment_frequency'  => $frequency,
            'status'             => AccountCreditLineExt::STATUS_ACTIVE,
            'descr'              => null,
        ]);
    }

    /**
     * Assert the three invariants on a freshly built schedule.
     */
    private function assertScheduleInvariants(
        array $payments,
        float $outstanding,
        Carbon $fromDate,
        int $termMonths
    ): void {
        $this->assertNotEmpty($payments, 'A positive-term line must produce at least one payment.');

        // Σ shares_due == outstanding exactly.
        $sum = round(array_sum(array_map(fn ($p) => (float) $p->shares_due, $payments)), 4);
        $this->assertSame(
            round($outstanding, 4),
            $sum,
            'Sum of shares_due must equal outstanding exactly.'
        );

        // Strictly increasing due_dates.
        $dates = array_map(fn ($p) => Carbon::parse($p->due_date), $payments);
        for ($i = 1; $i < count($dates); $i++) {
            $this->assertTrue(
                $dates[$i]->gt($dates[$i - 1]),
                "due_date series must be strictly increasing (row {$i})."
            );
        }

        // Last due_date pinned to maturity = fromDate + term_months.
        $maturity = $fromDate->copy()->addMonths($termMonths)->toDateString();
        $this->assertSame(
            $maturity,
            end($dates)->toDateString(),
            'Last due_date must equal maturity_date (fromDate + term_months).'
        );
    }

    /** Regression: exact-divisible quarterly term is unchanged. */
    public function test_exact_divisible_quarterly_term(): void
    {
        $from = Carbon::parse('2026-01-15');
        $line = $this->makeLine(12, AccountCreditLineExt::FREQUENCY_QUARTERLY, $from);

        $payments = $this->builder->build($line, 100.0, $from->copy());

        $this->assertCount(4, $payments);
        $this->assertScheduleInvariants($payments, 100.0, $from, 12);
    }

    /** Regression: monthly term ends exactly at maturity. */
    public function test_monthly_term(): void
    {
        $from = Carbon::parse('2026-03-31');
        $line = $this->makeLine(7, AccountCreditLineExt::FREQUENCY_MONTHLY, $from);

        $payments = $this->builder->build($line, 100.0, $from->copy());

        $this->assertCount(7, $payments);
        $this->assertScheduleInvariants($payments, 100.0, $from, 7);
    }

    /** Non-divisible quarterly: 10mo / 3 must still end at maturity. */
    public function test_non_divisible_quarterly_term_ends_at_maturity(): void
    {
        $from = Carbon::parse('2026-01-15');
        $line = $this->makeLine(10, AccountCreditLineExt::FREQUENCY_QUARTERLY, $from);

        $payments = $this->builder->build($line, 100.0, $from->copy());

        $this->assertScheduleInvariants($payments, 100.0, $from, 10);
    }

    /** Non-divisible annual: 14mo / 12 must still end at maturity. */
    public function test_non_divisible_annual_term_ends_at_maturity(): void
    {
        $from = Carbon::parse('2026-01-15');
        $line = $this->makeLine(14, AccountCreditLineExt::FREQUENCY_ANNUAL, $from);

        $payments = $this->builder->build($line, 100.0, $from->copy());

        $this->assertScheduleInvariants($payments, 100.0, $from, 14);
    }

    /** Sub-period term: 2mo quarterly must collapse to a single payment at maturity. */
    public function test_sub_period_term_single_payment_at_maturity(): void
    {
        $from = Carbon::parse('2026-01-15');
        $line = $this->makeLine(2, AccountCreditLineExt::FREQUENCY_QUARTERLY, $from);

        $payments = $this->builder->build($line, 100.0, $from->copy());

        $this->assertScheduleInvariants($payments, 100.0, $from, 2);
    }
}
