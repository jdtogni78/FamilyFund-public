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

    public function test_resolve_rates_defaults_to_fund_expected_with_bands(): void
    {
        // No overrides: expected = fund expected growth rate, bands = ×0.8/×1.2.
        $line = $this->makeLine(50.0);
        $expected = (float) $this->factory->fund->getExpectedGrowthRate();

        $rates = $this->simulator->resolveRates($line);

        $this->assertEqualsWithDelta($expected, $rates['expected'], 0.0001);
        $this->assertEqualsWithDelta($expected * 0.8, $rates['conservative'], 0.0001);
        $this->assertEqualsWithDelta($expected * 1.2, $rates['aggressive'], 0.0001);
    }

    public function test_resolve_rates_expected_override_rescales_bands(): void
    {
        // Overriding only `expected` re-scales both bands from the new anchor.
        $line = $this->makeLine(50.0);

        $rates = $this->simulator->resolveRates($line, ['expected' => 10.0]);

        $this->assertEqualsWithDelta(10.0, $rates['expected'], 0.0001);
        $this->assertEqualsWithDelta(8.0, $rates['conservative'], 0.0001);
        $this->assertEqualsWithDelta(12.0, $rates['aggressive'], 0.0001);
    }

    public function test_resolve_rates_individual_band_override_is_verbatim(): void
    {
        // A band override is taken verbatim; the others keep their defaults.
        $line = $this->makeLine(50.0);
        $expected = (float) $this->factory->fund->getExpectedGrowthRate();

        $rates = $this->simulator->resolveRates($line, ['aggressive' => 99.0]);

        $this->assertEqualsWithDelta($expected, $rates['expected'], 0.0001);
        $this->assertEqualsWithDelta($expected * 0.8, $rates['conservative'], 0.0001);
        $this->assertEqualsWithDelta(99.0, $rates['aggressive'], 0.0001);
    }

    public function test_resolve_rates_full_override_uses_all_three(): void
    {
        $line = $this->makeLine(50.0);

        $rates = $this->simulator->resolveRates($line, [
            'conservative' => 3.0,
            'expected'     => 6.0,
            'aggressive'   => 9.0,
        ]);

        $this->assertEqualsWithDelta(3.0, $rates['conservative'], 0.0001);
        $this->assertEqualsWithDelta(6.0, $rates['expected'], 0.0001);
        $this->assertEqualsWithDelta(9.0, $rates['aggressive'], 0.0001);
    }

    public function test_simulate_all_scenarios_honours_rate_overrides(): void
    {
        // The override flows through to each scenario's growth rate.
        $line = $this->makeLine(50.0);

        $results = $this->simulator->simulateAllScenarios($line, 25.0, ['expected' => 10.0]);

        $this->assertEqualsWithDelta(8.0, $results['conservative']->annual_growth_rate_pct, 0.0001);
        $this->assertEqualsWithDelta(10.0, $results['expected']->annual_growth_rate_pct, 0.0001);
        $this->assertEqualsWithDelta(12.0, $results['aggressive']->annual_growth_rate_pct, 0.0001);
    }

    public function test_solve_for_payment_all_scenarios_honours_rate_overrides(): void
    {
        $line = $this->makeLine(50.0);

        $all = $this->simulator->solveForPaymentAllScenarios($line, 12, [
            'conservative' => 3.0,
            'expected'     => 6.0,
            'aggressive'   => 9.0,
        ]);

        $this->assertEqualsWithDelta(3.0, $all['conservative']['result']->annual_growth_rate_pct, 0.0001);
        $this->assertEqualsWithDelta(6.0, $all['expected']['result']->annual_growth_rate_pct, 0.0001);
        $this->assertEqualsWithDelta(9.0, $all['aggressive']['result']->annual_growth_rate_pct, 0.0001);
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

    public function test_solve_for_payment_with_flat_share_value(): void
    {
        // outstanding=100 shares, share_value=$1.00, growth=0%, target_months=10
        //   shares_paid/month = payment/1.00; need 100 shares in 10 months → $10/month.
        $line = $this->makeLine(100.0);
        $payment = $this->simulator->solveForPayment($line, 10, 0.0, 1.0);

        $this->assertEqualsWithDelta(10.0, $payment, 0.10);
    }

    public function test_solve_for_payment_higher_growth_requires_higher_payment(): void
    {
        // Same line, same target months; aggressive growth makes shares
        // more expensive per dollar, so the required monthly payment to
        // retire the same outstanding share count by the deadline is higher.
        $line = $this->makeLine(100.0);
        $expectedRate = 5.0;
        $aggressiveRate = 50.0;

        $expectedPayment = $this->simulator->solveForPayment($line, 24, $expectedRate, 1.0);
        $aggressivePayment = $this->simulator->solveForPayment($line, 24, $aggressiveRate, 1.0);

        $this->assertGreaterThan(
            $expectedPayment,
            $aggressivePayment,
            'Aggressive growth should require higher monthly payment to hit the same target.'
        );
    }

    public function test_solve_for_payment_roundtrips_through_simulate(): void
    {
        // Pick a payment, simulate, then solveForPayment with the resulting
        // payoff_month — the recovered payment should match the original.
        $line = $this->makeLine(100.0);
        $original = 8.0;
        $result = $this->simulator->simulate($line, $original, 5.0, 1.0);
        $this->assertNotNull($result->payoff_month);

        $recovered = $this->simulator->solveForPayment(
            $line,
            $result->payoff_month,
            5.0,
            1.0
        );

        // Solver returns the smallest payment landing at or before target,
        // which may be slightly under the original (since the original
        // probably overshoots a bit). Allow a generous tolerance.
        $this->assertEqualsWithDelta($original, $recovered, 1.0);
    }

    public function test_solve_for_payment_handles_too_short_target(): void
    {
        // outstanding=100 shares, share_value=$1.00, growth=0%, target_months=1
        //   need to pay off everything in one month → $100/month.
        $line = $this->makeLine(100.0);
        $payment = $this->simulator->solveForPayment($line, 1, 0.0, 1.0);

        $this->assertEqualsWithDelta(100.0, $payment, 0.50);
    }

    public function test_total_paid_usd_excludes_unspent_final_month(): void
    {
        // owe 100 shares, $30/mo, $1.00 flat share value, 0% growth:
        //   M1-3 buy 30 shares ($30 each); M4 only 10 shares remain, so the
        //   final payment is capped to $10 — not a full $30. Total cash is
        //   $90 + $10 = $100, NOT $30 × 4 = $120. total_paid_usd must agree
        //   with total_paid_shares × price, never bill the unspent remainder.
        $line = $this->makeLine(100.0);
        $result = $this->simulator->simulate($line, 30.0, 0.0, 1.0);

        $this->assertSame(4, $result->payoff_month);
        $this->assertEqualsWithDelta(100.0, $result->total_paid_usd, 0.01);
        $this->assertEqualsWithDelta(100.0, $result->total_paid_shares, 0.01);
        $this->assertLessThan(120.0, $result->total_paid_usd, 'Final partial month must not be billed at the full payment.');
    }

    public function test_payoff_date_anchored_to_today_not_origination(): void
    {
        // The projection starts from the line's CURRENT outstanding and today's
        // share value, so payoff dates must be anchored to today — even for a
        // line originated long ago (a backdated draw). Origination + month
        // would put the payoff date in the past.
        $line = $this->makeLine(100.0);
        $line->origination_date = Carbon::today()->subYears(2)->toDateString();
        $line->maturity_date = Carbon::today()->subYears(1)->toDateString();
        $line->save();

        $result = $this->simulator->simulate($line, 10.0, 0.0, 1.0);

        $this->assertSame(10, $result->payoff_month);
        $this->assertNotNull($result->payoff_date);
        $this->assertSame(
            Carbon::today()->addMonths(10)->toDateString(),
            $result->payoff_date,
            'Payoff date should be today + payoff_month, not origination + month.'
        );
        $this->assertTrue(Carbon::parse($result->payoff_date)->gt(Carbon::today()));
        // First series row is one month out from today, not from origination.
        $this->assertSame(
            Carbon::today()->addMonths(1)->toDateString(),
            $result->monthly_series[0]['date']
        );
    }

    public function test_simulate_persists_nothing(): void
    {
        // Read-only guarantee: no scenario method may create transactions or
        // schedule rows. makeLine() saves the line directly (no DrawService),
        // so the only way these counts move is an unwanted side-effect.
        $line = $this->makeLine(100.0);

        $txBefore  = \Illuminate\Support\Facades\DB::table('transactions')->count();
        $payBefore = \Illuminate\Support\Facades\DB::table('credit_line_payments')->count();

        $this->simulator->simulate($line, 25.0, 7.0, 1.0);
        $this->simulator->simulateAllScenarios($line, 25.0);
        $this->simulator->solveForPaymentAllScenarios($line, 12);

        $this->assertSame($txBefore, \Illuminate\Support\Facades\DB::table('transactions')->count());
        $this->assertSame($payBefore, \Illuminate\Support\Facades\DB::table('credit_line_payments')->count());
    }
}
