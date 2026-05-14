<?php

namespace Tests\Unit\Services\CreditLine\Simulation;

use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Services\CreditLine\Simulation\PaymentSimulator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Phase 9: PaymentSimulator unit tests.
 *
 * Read-only "what if" projector — no persistence side-effects expected.
 */
class PaymentSimulatorTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private PaymentSimulator $simulator;

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

        $this->simulator = new PaymentSimulator();
    }

    private function makeLine(float $outstanding): AccountCreditLine
    {
        $line = new AccountCreditLine();
        $line->account_id = $this->factory->userAccount->id;
        $line->principal_shares = $outstanding;
        $line->outstanding_shares = $outstanding;
        $line->term_months = 12;
        $line->origination_date = Carbon::today()->toDateString();
        $line->maturity_date = Carbon::today()->addMonths(12)->toDateString();
        $line->payment_frequency = 'monthly';
        $line->status = 'active';
        $line->save();
        return $line;
    }

    public function test_simulate_pays_off_at_expected_month_with_flat_share_value(): void
    {
        // growth=0, monthly_payment=10, share_value=1.00, outstanding=100 shares
        //   shares_paid/month = 10/1 = 10, so 10 months to pay off, $100 total.
        $line = $this->makeLine(100.0);
        $result = $this->simulator->simulate($line, 10.0, 0.0, 1.0);

        $this->assertSame(10, $result->payoff_month);
        $this->assertEqualsWithDelta(100.0, $result->total_paid_usd, 0.01);
        $this->assertEqualsWithDelta(100.0, $result->total_paid_shares, 0.01);
        $this->assertFalse($result->capped);
        $this->assertNotNull($result->payoff_date);
    }

    public function test_higher_growth_means_slower_payoff(): void
    {
        // Same monthly payment, varying growth. Aggressive growth → share
        // price climbs faster → fewer shares purchased per dollar → slower
        // payoff than the expected scenario.
        // Use a larger growth gap so the difference is measurable over a
        // small number of monthly steps. The growth-effect on payoff month
        // grows non-linearly with rate; modest differences (7% vs 8.4%)
        // can round to the same integer month for a small principal.
        $line = $this->makeLine(100.0);

        $expectedRate = 5.0;
        $aggressiveRate = 50.0; // well above expected*1.2 — exercises the directional invariant

        $expected = $this->simulator->simulate($line, 5.0, $expectedRate, 1.0);
        $aggressive = $this->simulator->simulate($line, 5.0, $aggressiveRate, 1.0);

        $this->assertNotNull($expected->payoff_month);
        $this->assertNotNull($aggressive->payoff_month);
        $this->assertGreaterThan(
            $expected->payoff_month,
            $aggressive->payoff_month,
            'Aggressive growth should pay off slower than expected.'
        );
    }

    public function test_simulator_caps_at_600_months_for_too_small_payment(): void
    {
        // $1/month against 100 shares at $1.00 + 10% growth: in any given
        // month the dollar buys < 1 share once share price climbs; outstanding
        // is paid down slowly but the run should still terminate by the cap.
        // Use an even smaller payment to ensure non-payoff: $0.01/month
        // against 100 shares grows past affordability immediately.
        $line = $this->makeLine(100.0);
        $result = $this->simulator->simulate($line, 0.01, 10.0, 1.0);

        $this->assertNull($result->payoff_month);
        $this->assertTrue($result->capped);
        $this->assertNull($result->payoff_date);
        $this->assertCount(PaymentSimulator::MONTH_CAP, $result->monthly_series);
    }

    public function test_three_scenarios_use_correct_growth_multipliers(): void
    {
        $line = $this->makeLine(50.0);
        $results = $this->simulator->simulateAllScenarios($line, 25.0);

        $this->assertArrayHasKey('conservative', $results);
        $this->assertArrayHasKey('expected', $results);
        $this->assertArrayHasKey('aggressive', $results);

        $expectedRate = $results['expected']->annual_growth_rate_pct;
        $this->assertEqualsWithDelta(
            $expectedRate * 0.8,
            $results['conservative']->annual_growth_rate_pct,
            0.0001
        );
        $this->assertEqualsWithDelta(
            $expectedRate * 1.2,
            $results['aggressive']->annual_growth_rate_pct,
            0.0001
        );
    }

    public function test_simulate_zero_or_negative_payment_throws(): void
    {
        $line = $this->makeLine(100.0);

        $this->expectException(InvalidArgumentException::class);
        $this->simulator->simulate($line, 0.0, 7.0, 1.0);
    }

    public function test_simulate_negative_payment_throws(): void
    {
        $line = $this->makeLine(100.0);

        $this->expectException(InvalidArgumentException::class);
        $this->simulator->simulate($line, -5.0, 7.0, 1.0);
    }
}
