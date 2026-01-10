<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\FundTrait;
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
}
