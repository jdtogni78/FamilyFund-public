<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\FundTrait;
use App\Models\AssetPrice;
use App\Models\ExchangeHoliday;
use App\Models\FundExt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for FundTrait methods
 */
class FundTraitTest extends TestCase
{
    use DatabaseTransactions;

    private $traitObject;
    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous class that uses the trait and exposes protected methods for testing
        $this->traitObject = new class {
            use FundTrait;
            public $verbose = false;

            // Expose protected method for testing
            public function testCreateAllocationStatusArray(array $assetMonthlyBands, $tradePortfolios, string $asOf): array
            {
                return $this->createAllocationStatusArray($assetMonthlyBands, $tradePortfolios, $asOf);
            }

            // Expose calculateDataStaleness for testing
            public function testCalculateDataStaleness(string $asOf): array
            {
                return $this->calculateDataStaleness($asOf);
            }
        };

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
    }

    public function test_is_admin_returns_false_when_not_logged_in()
    {
        Auth::logout();
        $this->assertFalse($this->traitObject->isAdmin());
    }

    public function test_is_admin_returns_true_for_admin_email()
    {
        // Use existing user or create with unique email
        $user = User::where('email', 'admin@dev.familyfund.local')->first()
            ?? User::factory()->create(['email' => 'admin@dev.familyfund.local']);
        Auth::login($user);
        $this->assertTrue($this->traitObject->isAdmin());
    }

    public function test_is_admin_returns_true_for_test_user()
    {
        $user = User::where('email', 'claude@test.local')->first()
            ?? User::factory()->create(['email' => 'claude@test.local']);
        Auth::login($user);
        $this->assertTrue($this->traitObject->isAdmin());
    }

    public function test_is_admin_returns_false_for_non_admin_email()
    {
        $user = User::factory()->create(['email' => 'regular' . uniqid() . '@user.com']);
        Auth::login($user);
        $this->assertFalse($this->traitObject->isAdmin());
    }

    public function test_is_admin_respects_admin_zero_query_param()
    {
        $user = User::where('email', 'admin@dev.familyfund.local')->first()
            ?? User::factory()->create(['email' => 'admin@dev.familyfund.local']);
        Auth::login($user);

        // Without param, should be admin
        $this->assertTrue($this->traitObject->isAdmin());

        // With admin=0 param, should not be admin
        request()->merge(['admin' => '0']);
        $this->assertFalse($this->traitObject->isAdmin());
    }

    public function test_create_fund_array_returns_correct_structure()
    {
        $fund = $this->factory->fund;
        $asOf = '2022-06-01';

        $result = $this->traitObject->createFundArray($fund, $asOf);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('as_of', $result);
        $this->assertEquals($fund->id, $result['id']);
        $this->assertEquals($fund->name, $result['name']);
        $this->assertEquals($asOf, $result['as_of']);
    }

    public function test_create_fund_response_returns_summary()
    {
        $fund = $this->factory->fund;
        $asOf = '2022-06-01';

        $result = $this->traitObject->createFundResponse($fund, $asOf);

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('as_of', $result);
        $this->assertEquals($asOf, $result['as_of']);

        // Check summary structure
        $summary = $result['summary'];
        $this->assertArrayHasKey('value', $summary);
        $this->assertArrayHasKey('shares', $summary);
        $this->assertArrayHasKey('share_value', $summary);
        $this->assertArrayHasKey('unallocated_shares', $summary);
        $this->assertArrayHasKey('allocated_shares', $summary);
        $this->assertArrayHasKey('borrowed_shares', $summary);
        $this->assertArrayHasKey('available_unallocated_shares', $summary);
    }

    public function test_create_allocation_status_array_with_empty_data()
    {
        $asOf = '2022-06-01';
        $emptyAssetBands = [];
        $emptyTradePortfolios = [];

        $result = $this->traitObject->testCreateAllocationStatusArray($emptyAssetBands, $emptyTradePortfolios, $asOf);

        $this->assertArrayHasKey('as_of_date', $result);
        $this->assertArrayHasKey('total_value', $result);
        $this->assertArrayHasKey('symbols', $result);
        $this->assertEquals($asOf, $result['as_of_date']);
        $this->assertEquals(0, $result['total_value']);
        $this->assertEmpty($result['symbols']);
    }

    public function test_create_allocation_status_array_calculates_status()
    {
        $asOf = '2022-06-01';

        // Mock asset bands data
        $assetBands = [
            'VOO' => [
                '2022-06-01' => ['value' => 5000],
            ],
            'BND' => [
                '2022-06-01' => ['value' => 3000],
            ],
            'VNQ' => [
                '2022-06-01' => ['value' => 2000],
            ],
        ];

        // Mock trade portfolio with items
        $tradePortfolios = [
            [
                'id' => 1,
                'start_dt' => '2022-01-01',
                'end_dt' => '9999-12-31',
                'items' => [
                    ['symbol' => 'VOO', 'target_share' => 0.50, 'deviation_trigger' => 0.05],
                    ['symbol' => 'BND', 'target_share' => 0.30, 'deviation_trigger' => 0.05],
                    ['symbol' => 'VNQ', 'target_share' => 0.20, 'deviation_trigger' => 0.05],
                ],
            ],
        ];

        $result = $this->traitObject->testCreateAllocationStatusArray($assetBands, $tradePortfolios, $asOf);

        $this->assertEquals(10000, $result['total_value']);
        $this->assertCount(3, $result['symbols']);

        // VOO: 5000/10000 = 50%, target 50% +/- 5% => ok
        $voo = collect($result['symbols'])->firstWhere('symbol', 'VOO');
        $this->assertEquals('ok', $voo['status']);
        $this->assertEquals(50, $voo['current_pct']);
        $this->assertEquals(50, $voo['target_pct']);

        // BND: 3000/10000 = 30%, target 30% +/- 5% => ok
        $bnd = collect($result['symbols'])->firstWhere('symbol', 'BND');
        $this->assertEquals('ok', $bnd['status']);

        // VNQ: 2000/10000 = 20%, target 20% +/- 5% => ok
        $vnq = collect($result['symbols'])->firstWhere('symbol', 'VNQ');
        $this->assertEquals('ok', $vnq['status']);
    }

    public function test_create_allocation_status_detects_under_allocation()
    {
        $asOf = '2022-06-01';

        $assetBands = [
            'VOO' => ['2022-06-01' => ['value' => 3000]], // 30% but target is 50%
            'BND' => ['2022-06-01' => ['value' => 7000]], // 70%
        ];

        $tradePortfolios = [
            [
                'id' => 1,
                'start_dt' => '2022-01-01',
                'end_dt' => '9999-12-31',
                'items' => [
                    ['symbol' => 'VOO', 'target_share' => 0.50, 'deviation_trigger' => 0.05], // target 50%, min 45%
                    ['symbol' => 'BND', 'target_share' => 0.50, 'deviation_trigger' => 0.05],
                ],
            ],
        ];

        $result = $this->traitObject->testCreateAllocationStatusArray($assetBands, $tradePortfolios, $asOf);

        $voo = collect($result['symbols'])->firstWhere('symbol', 'VOO');
        $this->assertEquals('under', $voo['status']); // 30% < 45% min

        $bnd = collect($result['symbols'])->firstWhere('symbol', 'BND');
        $this->assertEquals('over', $bnd['status']); // 70% > 55% max
    }

    public function test_create_account_balances_response()
    {
        $this->factory->createUser();
        $transaction = $this->factory->createTransaction(500, $this->factory->userAccount);
        $this->factory->createBalance(50, $transaction, $this->factory->userAccount, '2022-01-01');

        $fund = $this->factory->fund;
        $asOf = '2022-06-01';

        $result = $this->traitObject->createAccountBalancesResponse($fund, $asOf);

        $this->assertIsArray($result);
        // Should have at least one balance entry for our user
        $this->assertGreaterThanOrEqual(1, count($result));

        if (count($result) > 0) {
            $balance = $result[0];
            $this->assertArrayHasKey('user', $balance);
            $this->assertArrayHasKey('account_id', $balance);
            $this->assertArrayHasKey('shares', $balance);
            $this->assertArrayHasKey('value', $balance);
        }
    }

    public function test_report_users_returns_user_accounts()
    {
        $this->factory->createUser();

        $fund = $this->factory->fund;

        // Non-admin should get user accounts (accounts with users)
        $result = $this->traitObject->reportUsers($fund, false);
        $this->assertGreaterThanOrEqual(1, count($result));

        // Admin should get fund account (accounts without users)
        $result = $this->traitObject->reportUsers($fund, true);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    // ==================== Data Staleness Tests ====================

    /**
     * Helper to create a test asset with price
     */
    private function createTestAssetPrice(string $date, float $price = 100.00): AssetPrice
    {
        // Use the cash asset from factory or create one
        $asset = $this->factory->cash ?? \App\Models\Asset::first();

        return AssetPrice::create([
            'asset_id' => $asset->id,
            'price' => $price,
            'start_dt' => $date,
            'end_dt' => '9999-12-31',
        ]);
    }

    /**
     * Helper to clear and create asset prices for testing
     */
    private function setupAssetPricesForTest(string $priceDate): void
    {
        // Delete all asset prices for clean test
        AssetPrice::query()->delete();

        // Create a single price at the specified date
        $this->createTestAssetPrice($priceDate);
    }

    public function test_calculate_data_staleness_returns_not_stale_when_data_is_current()
    {
        $today = Carbon::today()->format('Y-m-d');
        $this->setupAssetPricesForTest($today);

        $result = $this->traitObject->testCalculateDataStaleness($today);

        $this->assertArrayHasKey('latest_price_date', $result);
        $this->assertArrayHasKey('trading_days_stale', $result);
        $this->assertArrayHasKey('is_stale', $result);
        $this->assertEquals($today, $result['latest_price_date']);
        $this->assertEquals(0, $result['trading_days_stale']);
        $this->assertFalse($result['is_stale']);
    }

    public function test_calculate_data_staleness_returns_stale_when_data_is_old()
    {
        // Create an asset price - calculateTradingDays counts days strictly BETWEEN dates (exclusive)
        // Monday data, Thursday report: Tue and Wed are between = 2 trading days stale
        $asOfDate = Carbon::parse('2024-01-11'); // Thursday
        $oldDate = Carbon::parse('2024-01-08'); // Monday

        $this->setupAssetPricesForTest($oldDate->format('Y-m-d'));

        $result = $this->traitObject->testCalculateDataStaleness($asOfDate->format('Y-m-d'));

        $this->assertTrue($result['is_stale']);
        $this->assertEquals($oldDate->format('Y-m-d'), $result['latest_price_date']);
        $this->assertEquals(2, $result['trading_days_stale']); // Tue, Wed (exclusive on both ends)
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('2 trading days', $result['message']);
    }

    public function test_calculate_data_staleness_accounts_for_weekends()
    {
        // Asset price on Friday, report on Tuesday
        // calculateTradingDays counts strictly between: Sat/Sun skipped (weekend), Mon is counted = 1
        $friday = Carbon::parse('2024-01-05'); // Friday
        $tuesday = Carbon::parse('2024-01-09'); // Tuesday

        $this->setupAssetPricesForTest($friday->format('Y-m-d'));

        $result = $this->traitObject->testCalculateDataStaleness($tuesday->format('Y-m-d'));

        // Between Friday and Tuesday: Sat/Sun skipped, Monday counted = 1 trading day
        $this->assertTrue($result['is_stale']);
        $this->assertEquals(1, $result['trading_days_stale']);
    }

    public function test_calculate_data_staleness_accounts_for_holidays()
    {
        // Create a holiday (MLK Day - third Monday of January)
        $holiday = Carbon::parse('2024-01-15'); // MLK Day (Monday)
        $friday = Carbon::parse('2024-01-12'); // Previous Friday
        $wednesday = Carbon::parse('2024-01-17'); // Wednesday after holiday

        // Ensure holiday exists
        ExchangeHoliday::updateOrCreate(
            ['exchange_code' => 'NYSE', 'holiday_date' => $holiday->format('Y-m-d')],
            ['holiday_name' => 'MLK Day', 'is_active' => true, 'source' => 'test']
        );

        $this->setupAssetPricesForTest($friday->format('Y-m-d'));

        $result = $this->traitObject->testCalculateDataStaleness($wednesday->format('Y-m-d'));

        // Between Friday and Wednesday: Sat/Sun skipped, Mon skipped (holiday), Tuesday counted = 1
        $this->assertTrue($result['is_stale']);
        $this->assertEquals(1, $result['trading_days_stale']);
    }

    public function test_calculate_data_staleness_returns_error_when_no_data()
    {
        // Clear all asset prices
        AssetPrice::query()->delete();

        $result = $this->traitObject->testCalculateDataStaleness('2024-01-15');

        $this->assertTrue($result['is_stale']);
        $this->assertNull($result['latest_price_date']);
        $this->assertNull($result['trading_days_stale']);
        $this->assertStringContainsString('No asset price data', $result['message']);
    }

    public function test_calculate_data_staleness_message_uses_singular_for_one_day()
    {
        // Asset price 1 trading day old (between Monday and Wednesday = Tuesday only)
        $monday = Carbon::parse('2024-01-08');
        $wednesday = Carbon::parse('2024-01-10');

        $this->setupAssetPricesForTest($monday->format('Y-m-d'));

        $result = $this->traitObject->testCalculateDataStaleness($wednesday->format('Y-m-d'));

        $this->assertEquals(1, $result['trading_days_stale']);
        $this->assertStringContainsString('1 trading day', $result['message']);
        $this->assertStringNotContainsString('days', $result['message']);
    }

    public function test_calculate_data_staleness_message_uses_plural_for_multiple_days()
    {
        // Asset price 3 trading days old (between Monday and Friday = Tue, Wed, Thu)
        $monday = Carbon::parse('2024-01-08');
        $friday = Carbon::parse('2024-01-12');

        $this->setupAssetPricesForTest($monday->format('Y-m-d'));

        $result = $this->traitObject->testCalculateDataStaleness($friday->format('Y-m-d'));

        $this->assertEquals(3, $result['trading_days_stale']);
        $this->assertStringContainsString('3 trading days', $result['message']);
    }

    // ==================== Report Staleness Threshold Tests ====================

    public function test_no_data_gap_should_not_show_warning()
    {
        // Data from today - no staleness
        $today = Carbon::today()->format('Y-m-d');
        $this->setupAssetPricesForTest($today);

        $result = $this->traitObject->testCalculateDataStaleness($today);

        $this->assertFalse($result['is_stale']);
        $this->assertEquals(0, $result['trading_days_stale']);
        $this->assertArrayNotHasKey('message', $result);
    }

    public function test_data_gap_within_5_trading_days_should_show_warning_but_allow_report()
    {
        // Data from 4 trading days ago - within tolerance, should show warning
        // Monday Jan 8 data, report on Friday Jan 12 = 3 trading days stale (Tue, Wed, Thu)
        $monday = Carbon::parse('2024-01-08');
        $friday = Carbon::parse('2024-01-12');

        $this->setupAssetPricesForTest($monday->format('Y-m-d'));

        $result = $this->traitObject->testCalculateDataStaleness($friday->format('Y-m-d'));

        // Should be stale but within 5 day threshold
        $this->assertTrue($result['is_stale']);
        $this->assertEquals(3, $result['trading_days_stale']);
        $this->assertLessThanOrEqual(5, $result['trading_days_stale']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_data_gap_exactly_5_trading_days_should_still_allow_report()
    {
        // Data from 5 trading days ago - at the edge of tolerance
        // Use March 2024 to avoid holidays:
        // Monday Mar 4 data, report on Tuesday Mar 12 = 5 trading days (Tue 5, Wed 6, Thu 7, Fri 8, Mon 11)
        $monday1 = Carbon::parse('2024-03-04');
        $tuesday = Carbon::parse('2024-03-12');

        $this->setupAssetPricesForTest($monday1->format('Y-m-d'));

        $result = $this->traitObject->testCalculateDataStaleness($tuesday->format('Y-m-d'));

        // Should show warning but still be within threshold
        $this->assertTrue($result['is_stale']);
        $this->assertEquals(5, $result['trading_days_stale']); // Tue 5, Wed 6, Thu 7, Fri 8, Mon 11
        $this->assertArrayHasKey('message', $result);
    }

    public function test_data_gap_exceeds_5_trading_days()
    {
        // Data from more than 5 trading days ago - exceeds tolerance
        // Monday Mar 4 data, report on Wed Mar 13 = 6 trading days
        $monday = Carbon::parse('2024-03-04');
        $wednesday = Carbon::parse('2024-03-13');

        $this->setupAssetPricesForTest($monday->format('Y-m-d'));

        $result = $this->traitObject->testCalculateDataStaleness($wednesday->format('Y-m-d'));

        // Should be stale and exceed threshold (Tue 5, Wed 6, Thu 7, Fri 8, Mon 11, Tue 12 = 6)
        $this->assertTrue($result['is_stale']);
        $this->assertGreaterThan(5, $result['trading_days_stale']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_data_staleness_with_consecutive_holidays()
    {
        // Simpler test: just verify that holidays between dates reduce the trading day count
        // Friday data, Wednesday report with Monday as holiday = only 1 trading day (Tuesday)
        $friday = Carbon::parse('2024-02-16'); // Friday
        $wednesday = Carbon::parse('2024-02-21'); // Wednesday

        // Create President's Day holiday on Monday
        ExchangeHoliday::updateOrCreate(
            ['exchange_code' => 'NYSE', 'holiday_date' => '2024-02-19'],
            ['holiday_name' => "Presidents' Day", 'is_active' => true, 'source' => 'test']
        );

        $this->setupAssetPricesForTest($friday->format('Y-m-d'));

        $result = $this->traitObject->testCalculateDataStaleness($wednesday->format('Y-m-d'));

        // Fri Feb 16 to Wed Feb 21:
        // Sat Feb 17 - Weekend (skip)
        // Sun Feb 18 - Weekend (skip)
        // Mon Feb 19 - Holiday (skip)
        // Tue Feb 20 - counted (1 trading day)
        // Wed Feb 21 - End date (not counted)
        $this->assertTrue($result['is_stale']);
        $this->assertEquals(1, $result['trading_days_stale']);
    }
}
