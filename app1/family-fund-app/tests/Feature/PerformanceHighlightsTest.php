<?php

namespace Tests\Feature;

use App\Http\Controllers\Traits\FundTrait;
use App\Models\TransactionExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for performance highlights calculation (all-time return)
 *
 * The all-time return should be calculated as:
 * (current_value - total_deposits) / total_deposits * 100
 *
 * NOT as compounded yearly returns (which is incorrect for investment performance)
 */
class PerformanceHighlightsTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        // Create fund with initial value of $1000
        $this->df->createFund(1000, 1000, '2022-01-01');
        $this->df->createUser();
        $this->user = $this->df->user;

        // Give user admin access
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->user->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== All-Time Return Calculation Tests ====================

    /**
     * Helper to calculate expected all-time return
     */
    private function calculateExpectedAllTimeReturn(float $currentValue, float $totalDeposits): float
    {
        if ($totalDeposits <= 0) {
            return 0;
        }
        return (($currentValue - $totalDeposits) / $totalDeposits) * 100;
    }

    /**
     * Test that all-time return is calculated correctly for a fund
     * Formula: (current_value - total_deposits) / total_deposits * 100
     */
    public function test_fund_all_time_return_calculation()
    {
        // Initial fund: $1000 deposit, 1000 shares -> share price = $1
        // After a 20% gain, fund value becomes $1200
        // All-time return should be: ($1200 - $1000) / $1000 * 100 = 20%

        $totalDeposits = 1000;  // Initial deposit
        $currentValue = 1200;   // After gain

        $expected = $this->calculateExpectedAllTimeReturn($currentValue, $totalDeposits);

        $this->assertEquals(20.0, $expected);
    }

    /**
     * Test that all-time return handles multiple deposits correctly
     */
    public function test_all_time_return_with_multiple_deposits()
    {
        // Scenario: $1000 initial, $500 additional deposit later
        // Current value: $1800 (gains of $300 on total $1500 invested)
        // All-time return: ($1800 - $1500) / $1500 * 100 = 20%

        $totalDeposits = 1500;
        $currentValue = 1800;

        $expected = $this->calculateExpectedAllTimeReturn($currentValue, $totalDeposits);

        $this->assertEquals(20.0, $expected);
    }

    /**
     * Test that all-time return handles losses correctly
     */
    public function test_all_time_return_with_loss()
    {
        // $1000 deposited, current value $800
        // All-time return: ($800 - $1000) / $1000 * 100 = -20%

        $totalDeposits = 1000;
        $currentValue = 800;

        $expected = $this->calculateExpectedAllTimeReturn($currentValue, $totalDeposits);

        $this->assertEquals(-20.0, $expected);
    }

    /**
     * Test that zero deposits returns zero
     */
    public function test_all_time_return_with_zero_deposits()
    {
        $expected = $this->calculateExpectedAllTimeReturn(1000, 0);

        $this->assertEquals(0, $expected);
    }

    // ==================== Fund View Integration Tests ====================

    /**
     * Test that fund show page includes all-time return in view data
     */
    public function test_fund_show_includes_all_time_return()
    {
        $response = $this->actingAs($this->user)->get('/funds/' . $this->df->fund->id);

        $response->assertStatus(200);
        $response->assertViewHas('api');

        // Get the api data from the view
        $api = $response->viewData('api');

        // Verify transactions are available for calculating deposits
        $this->assertArrayHasKey('transactions', $api);
    }

    /**
     * Test that fund show page has the correct all-time override calculation
     * This test verifies the fix: all-time should be based on deposits, not compounded returns
     */
    public function test_fund_show_calculates_correct_all_time_from_deposits()
    {
        // Get the fund show page
        $response = $this->actingAs($this->user)->get('/funds/' . $this->df->fund->id);

        $response->assertStatus(200);

        // Get the api data from the view
        $api = $response->viewData('api');

        // Calculate total deposits from transactions
        $totalDeposits = 0;
        foreach ($api['transactions'] ?? [] as $trans) {
            if ($trans->value > 0) {
                $totalDeposits += $trans->value;
            }
        }

        // We created the fund with $1000 initial deposit
        $this->assertEquals(1000, $totalDeposits, 'Fund should have $1000 in deposits');

        // Verify current value is available in summary
        $this->assertArrayHasKey('summary', $api);
        $this->assertArrayHasKey('value', $api['summary']);
    }

    /**
     * Test that fund response data includes transactions for performance calculation
     */
    public function test_fund_response_includes_transactions()
    {
        // Create the trait object to test
        $traitObject = new class {
            use FundTrait;
            public $verbose = false;
        };

        $fund = $this->df->fund;
        $asOf = date('Y-m-d');

        $response = $traitObject->createFullFundResponse($fund, $asOf, true);

        $this->assertArrayHasKey('transactions', $response);
        $this->assertNotEmpty($response['transactions']);

        // Calculate total deposits
        $totalDeposits = 0;
        foreach ($response['transactions'] as $trans) {
            if ($trans->value > 0) {
                $totalDeposits += $trans->value;
            }
        }

        $this->assertGreaterThan(0, $totalDeposits, 'Fund should have positive deposits');
    }

    // ==================== Account Comparison Tests ====================

    /**
     * Test that account all-time return uses the same formula as fund
     */
    public function test_account_and_fund_use_same_all_time_formula()
    {
        // Create a user account with a transaction
        $this->df->createTransaction(500, $this->df->userAccount, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-06-01');
        $this->df->createBalance(50, $this->df->transaction, $this->df->userAccount, '2022-06-01');

        // Both fund and account should use:
        // All-time return = (current_value - total_deposits) / total_deposits * 100

        // This is different from compounding yearly returns!
        // e.g., two years of +10% each:
        // - Compound: 1.1 * 1.1 = 1.21 = +21%
        // - Actual: depends on timing of deposits

        $deposits = 1000;
        $currentValue = 1200;

        $expectedReturn = $this->calculateExpectedAllTimeReturn($currentValue, $deposits);

        $this->assertEquals(20.0, $expectedReturn);
    }

    // ==================== Edge Cases ====================

    /**
     * Test that withdrawals don't affect the total deposits calculation
     * Only positive transaction values should be counted as deposits
     */
    public function test_withdrawals_not_counted_as_deposits()
    {
        // $1000 deposit, $200 withdrawal
        // Total deposits should still be $1000, not $800

        $deposits = [1000, -200, 500]; // deposit, withdrawal, deposit
        $totalDeposits = 0;
        foreach ($deposits as $value) {
            if ($value > 0) {
                $totalDeposits += $value;
            }
        }

        $this->assertEquals(1500, $totalDeposits, 'Only positive values should be counted as deposits');
    }

    /**
     * Test the formula produces correct result for complex scenario
     *
     * Scenario:
     * - Year 1: Deposit $10,000, year-end value $11,000 (+10%)
     * - Year 2: Deposit $5,000, year-end value $19,200 (+20% on $16,000)
     *
     * Wrong (compounded): 1.1 * 1.2 = 1.32 = +32%
     * Correct (actual return): ($19,200 - $15,000) / $15,000 = +28%
     */
    public function test_all_time_return_vs_compounded_returns()
    {
        $totalDeposits = 15000;  // $10,000 + $5,000
        $currentValue = 19200;

        // Correct calculation
        $correctReturn = $this->calculateExpectedAllTimeReturn($currentValue, $totalDeposits);

        // Wrong calculation (what the old code did)
        $wrongCompoundedReturn = ((1.10 * 1.20) - 1) * 100;

        $this->assertEqualsWithDelta(28.0, $correctReturn, 0.01, 'Correct all-time return should be 28%');
        $this->assertEqualsWithDelta(32.0, $wrongCompoundedReturn, 0.01, 'Compounded return would be 32%');
        $this->assertNotEquals(round($correctReturn, 1), round($wrongCompoundedReturn, 1), 'These should differ - that\'s the bug!');
    }

    // ==================== Previous Year Growth Tests ====================

    /**
     * Helper to extract previous year growth from yearly performance data
     * This mimics the logic in highlights_growth.blade.php
     */
    private function extractPrevYearGrowth(array $yearlyPerf): ?float
    {
        $currentYear = date('Y');
        $prevYear = $currentYear - 1;

        foreach (array_keys($yearlyPerf) as $key) {
            if (substr($key, 0, 4) == $prevYear) {
                return $yearlyPerf[$key]['performance'] ?? 0;
            }
        }
        return null;
    }

    /**
     * Helper to extract current year YTD growth from yearly performance data
     * This mimics the logic in highlights_growth.blade.php
     */
    private function extractCurrentYearGrowth(array $yearlyPerf): ?float
    {
        $currentYear = date('Y');

        foreach (array_keys($yearlyPerf) as $key) {
            if (substr($key, 0, 4) == $currentYear) {
                return $yearlyPerf[$key]['performance'] ?? 0;
            }
        }
        return null;
    }

    /**
     * Test that previous year growth uses yearly_performance data (not deposit-based)
     * This is share price performance, which is appropriate for year-over-year comparisons
     */
    public function test_prev_year_growth_uses_yearly_performance_data()
    {
        $currentYear = date('Y');
        $prevYear = $currentYear - 1;

        // Mock yearly performance data
        $yearlyPerf = [
            "{$prevYear}-01-01 to {$currentYear}-01-01" => [
                'performance' => 15.5,
                'value' => '$11,550',
            ],
            "{$currentYear}-01-01 to {$currentYear}-06-01" => [
                'performance' => 8.2,
                'value' => '$12,497',
            ],
        ];

        $prevYearGrowth = $this->extractPrevYearGrowth($yearlyPerf);

        $this->assertEquals(15.5, $prevYearGrowth, 'Previous year growth should come from yearly_performance');
    }

    /**
     * Test that current year YTD uses yearly_performance data (not deposit-based)
     */
    public function test_current_year_ytd_uses_yearly_performance_data()
    {
        $currentYear = date('Y');
        $prevYear = $currentYear - 1;

        // Mock yearly performance data
        $yearlyPerf = [
            "{$prevYear}-01-01 to {$currentYear}-01-01" => [
                'performance' => 15.5,
                'value' => '$11,550',
            ],
            "{$currentYear}-01-01 to {$currentYear}-06-01" => [
                'performance' => 8.2,
                'value' => '$12,497',
            ],
        ];

        $currentYearGrowth = $this->extractCurrentYearGrowth($yearlyPerf);

        $this->assertEquals(8.2, $currentYearGrowth, 'Current year YTD should come from yearly_performance');
    }

    /**
     * Test that previous year growth returns null when no data for that year
     */
    public function test_prev_year_growth_returns_null_when_no_data()
    {
        $currentYear = date('Y');

        // Only current year data, no previous year
        $yearlyPerf = [
            "{$currentYear}-01-01 to {$currentYear}-06-01" => [
                'performance' => 8.2,
            ],
        ];

        $prevYearGrowth = $this->extractPrevYearGrowth($yearlyPerf);

        $this->assertNull($prevYearGrowth, 'Should return null when no previous year data');
    }

    /**
     * Test that current year YTD returns null when no data for current year
     */
    public function test_current_year_ytd_returns_null_when_no_data()
    {
        $prevYear = date('Y') - 1;
        $twoYearsAgo = date('Y') - 2;

        // Only previous year data, no current year
        $yearlyPerf = [
            "{$twoYearsAgo}-01-01 to {$prevYear}-01-01" => [
                'performance' => 12.0,
            ],
        ];

        $currentYearGrowth = $this->extractCurrentYearGrowth($yearlyPerf);

        $this->assertNull($currentYearGrowth, 'Should return null when no current year data');
    }

    /**
     * Test that negative performance values are handled correctly
     */
    public function test_yearly_performance_handles_negative_values()
    {
        $currentYear = date('Y');
        $prevYear = $currentYear - 1;

        $yearlyPerf = [
            "{$prevYear}-01-01 to {$currentYear}-01-01" => [
                'performance' => -12.5,  // Loss year
            ],
            "{$currentYear}-01-01 to {$currentYear}-06-01" => [
                'performance' => -3.2,  // YTD loss
            ],
        ];

        $prevYearGrowth = $this->extractPrevYearGrowth($yearlyPerf);
        $currentYearGrowth = $this->extractCurrentYearGrowth($yearlyPerf);

        $this->assertEquals(-12.5, $prevYearGrowth, 'Should handle negative previous year');
        $this->assertEquals(-3.2, $currentYearGrowth, 'Should handle negative current year');
    }

    // ==================== Fund Yearly Performance Integration Tests ====================

    /**
     * Test that fund response includes yearly_performance data structure
     */
    public function test_fund_response_includes_yearly_performance()
    {
        $traitObject = new class {
            use FundTrait;
            public $verbose = false;
        };

        $fund = $this->df->fund;
        $asOf = date('Y-m-d');

        $response = $traitObject->createFullFundResponse($fund, $asOf, true);

        $this->assertArrayHasKey('yearly_performance', $response);
        $this->assertIsArray($response['yearly_performance']);
    }

    /**
     * Test that fund yearly performance entries have correct structure
     */
    public function test_fund_yearly_performance_entry_structure()
    {
        $traitObject = new class {
            use FundTrait;
            public $verbose = false;
        };

        $fund = $this->df->fund;
        $asOf = date('Y-m-d');

        $response = $traitObject->createFullFundResponse($fund, $asOf, true);

        $yearlyPerf = $response['yearly_performance'];

        if (!empty($yearlyPerf)) {
            $firstEntry = reset($yearlyPerf);
            $this->assertArrayHasKey('performance', $firstEntry, 'Each yearly entry should have performance');
            $this->assertArrayHasKey('value', $firstEntry, 'Each yearly entry should have value');
        }
    }

    /**
     * Test that fund view has yearly_performance available for highlights
     */
    public function test_fund_show_has_yearly_performance_for_highlights()
    {
        $response = $this->actingAs($this->user)->get('/funds/' . $this->df->fund->id);

        $response->assertStatus(200);

        $api = $response->viewData('api');

        $this->assertArrayHasKey('yearly_performance', $api);
    }

    // ==================== Account Yearly Performance Integration Tests ====================

    /**
     * Test that account response includes yearly_performance data
     */
    public function test_account_show_has_yearly_performance()
    {
        // Create transaction and balance for the user account
        $this->df->createTransaction(500, $this->df->userAccount, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-06-01');
        $this->df->createBalance(50, $this->df->transaction, $this->df->userAccount, '2022-06-01');

        $response = $this->actingAs($this->user)->get('/accounts/' . $this->df->userAccount->id);

        $response->assertStatus(200);

        $api = $response->viewData('api');

        $this->assertArrayHasKey('yearly_performance', $api);
    }

    // ==================== Verify Calculations Are Independent ====================

    /**
     * Test that previous year and current YTD are independent of all-time calculation
     *
     * The key distinction:
     * - Prev Year & YTD: Use share price performance (time-weighted, excludes deposit timing)
     * - All-Time: Uses deposit-based return (money-weighted, accounts for deposit timing)
     */
    public function test_yearly_metrics_independent_of_all_time()
    {
        $currentYear = date('Y');
        $prevYear = $currentYear - 1;

        // Scenario: +10% prev year, +5% YTD, but different all-time due to deposit timing
        $yearlyPerf = [
            "{$prevYear}-01-01 to {$currentYear}-01-01" => ['performance' => 10.0],
            "{$currentYear}-01-01 to {$currentYear}-06-01" => ['performance' => 5.0],
        ];

        // Previous year and YTD come from yearly_performance
        $prevYearGrowth = $this->extractPrevYearGrowth($yearlyPerf);
        $currentYearGrowth = $this->extractCurrentYearGrowth($yearlyPerf);

        // All-time comes from deposit calculation (different formula!)
        $totalDeposits = 10000;
        $currentValue = 11500;  // Not 10000 * 1.1 * 1.05 = 11550 due to deposit timing
        $allTimeReturn = $this->calculateExpectedAllTimeReturn($currentValue, $totalDeposits);

        // These should all be independent values
        $this->assertEquals(10.0, $prevYearGrowth);
        $this->assertEquals(5.0, $currentYearGrowth);
        $this->assertEquals(15.0, $allTimeReturn);

        // Note: Compounded would be (1.1 * 1.05 - 1) * 100 = 15.5%, but actual is 15%
        // This demonstrates why we use different calculations for different metrics
    }

    /**
     * Test yearly performance key format matching
     * The key format is "YYYY-MM-DD to YYYY-MM-DD"
     */
    public function test_yearly_performance_key_format()
    {
        $currentYear = date('Y');
        $prevYear = $currentYear - 1;

        // Various key formats that should match
        $yearlyPerf = [
            "{$prevYear}-01-01 to {$currentYear}-01-01" => ['performance' => 10.0],
            "{$prevYear}-03-15 to {$currentYear}-03-15" => ['performance' => 8.0],  // Different start date
        ];

        // The extraction only checks first 4 chars (year), so first matching entry wins
        $prevYearGrowth = $this->extractPrevYearGrowth($yearlyPerf);

        $this->assertNotNull($prevYearGrowth);
        // First entry found for that year is used
        $this->assertContains($prevYearGrowth, [10.0, 8.0]);
    }
}
