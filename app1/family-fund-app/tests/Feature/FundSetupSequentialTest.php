<?php

namespace Tests\Feature;

use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\FundExt;
use App\Models\Portfolio;
use App\Models\PortfolioExt;
use App\Models\TransactionExt;
use App\Models\User;
use App\Models\AccountBalance;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Sequential and realistic scenario tests for Fund Setup
 *
 * Tests complex workflows like:
 * - Creating multiple funds in sequence
 * - Monarch-like setup with 16 portfolios
 * - Multiple transactions per fund
 * - Real-world data scenarios
 *
 * IMPORTANT: All funds under test are created via post(route('funds.storeWithSetup'))
 * DataFactory is ONLY used for:
 * - Creating test user in setUp()
 * - Creating supporting entities (assets, transactions, etc.)
 * - Creating "existing" legacy funds for integration testing
 */
class FundSetupSequentialTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required CASH asset for transaction processing if not exists
        \App\Models\Asset::firstOrCreate(
            ['data_source' => 'MANUAL', 'name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'SYSTEM', 'display_group' => 'Cash']
        );

        // Create test user directly (not via DataFactory since that requires a fund)
        $this->df = new DataFactory();
        $this->user = User::factory()->create();

        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->user->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);
    }

    // ==================== Multiple Portfolio Scenarios ====================

    public function test_creates_fund_with_16_portfolios_monarch_scenario()
    {
        // Monarch account sources (realistic data)
        $monarchSources = [
            'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX',
            'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX',
            'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX',
            'MONARCH_SCHW_ELA',  'MONARCH_IBKR_XXXX', 'MONARCH_IBKR_XXXX',
            'MONARCH_IBKR_XXXX', 'MONARCH_MERR_00A1', 'MONARCH_MERR_1000A',
            'MONARCH_MERR_1000B',
        ];

        $fundCountBefore = Fund::count();
        $portfolioCountBefore = Portfolio::count();

        // Create fund via API
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Monarch Consolidated',
                'goal' => 'Consolidated view of all Monarch accounts',
                'portfolio_source' => $monarchSources[0], // First portfolio created with fund
                'create_initial_transaction' => true,
                'initial_shares' => 1,
                'initial_value' => 0.01,
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Monarch Consolidated')->first();
        $this->assertNotNull($fund);

        // Verify first portfolio created
        $this->assertEquals($portfolioCountBefore + 1, Portfolio::count());

        // Now create remaining 15 portfolios sequentially
        foreach (array_slice($monarchSources, 1) as $source) {
            $portfolio = Portfolio::create([
                'fund_id' => $fund->id,
                'source' => $source,
            ]);
            $this->assertNotNull($portfolio);
        }

        // Verify all 16 portfolios exist
        $portfolios = Portfolio::where('fund_id', $fund->id)->get();
        $this->assertCount(16, $portfolios);

        // Verify each source
        foreach ($monarchSources as $source) {
            $this->assertTrue(
                $portfolios->contains('source', $source),
                "Missing portfolio: $source"
            );
        }
    }

    public function test_creates_multiple_portfolios_in_loop()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Multi Portfolio Fund',
                'portfolio_source' => 'PORTFOLIO_1',
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Multi Portfolio Fund')->first();

        // Create 10 additional portfolios
        $portfolioCount = 10;
        for ($i = 2; $i <= $portfolioCount + 1; $i++) {
            $portfolio = Portfolio::create([
                'fund_id' => $fund->id,
                'source' => "PORTFOLIO_$i",
            ]);
            $this->assertNotNull($portfolio);
            $this->assertEquals("PORTFOLIO_$i", $portfolio->source);
        }

        // Verify total count (1 initial + 10 additional)
        $portfolios = Portfolio::where('fund_id', $fund->id)->get();
        $this->assertCount(11, $portfolios);
    }

    // ==================== Sequential Fund Creation ====================

    public function test_creates_multiple_funds_sequentially()
    {
        $fundNames = [
            'Retirement Fund',
            'Education Fund',
            'Emergency Fund',
            'Investment Fund',
            'Tax Optimized Fund',
        ];

        $createdFunds = [];

        foreach ($fundNames as $index => $fundName) {
            $response = $this->actingAs($this->user)
                ->post(route('funds.storeWithSetup'), [
                    'name' => $fundName,
                    'goal' => "Goal for $fundName",
                    'portfolio_source' => "FUND_" . ($index + 1) . "_PORTFOLIO",
                    'create_initial_transaction' => true,
                    'initial_shares' => 100 * ($index + 1),
                    'initial_value' => 1000.00 * ($index + 1),
                    'preview' => 0,
                ]);

            $response->assertStatus(302);

            $fund = Fund::where('name', $fundName)->first();
            $this->assertNotNull($fund);
            $createdFunds[] = $fund;
        }

        // Verify all funds created
        $this->assertCount(5, $createdFunds);

        // Verify each fund has correct structure
        foreach ($createdFunds as $fund) {
            $account = AccountExt::where('fund_id', $fund->id)
                ->whereNull('user_id')
                ->first();
            $this->assertNotNull($account);

            $portfolio = Portfolio::where('fund_id', $fund->id)->first();
            $this->assertNotNull($portfolio);

            $transaction = TransactionExt::where('account_id', $account->id)->first();
            $this->assertNotNull($transaction);
        }
    }

    // ==================== Sequential Transaction Scenarios ====================

    public function test_creates_fund_then_multiple_transactions()
    {
        // Create fund
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Sequential Transaction Fund',
                'portfolio_source' => 'SEQ_TRANS_FUND',
                'create_initial_transaction' => true,
                'initial_shares' => 1000,
                'initial_value' => 1000.00,
                'preview' => 0,
            ]);

        $fund = FundExt::where('name', 'Sequential Transaction Fund')->first();
        $account = $fund->account();

        // Create additional transactions
        $transactionData = [
            ['type' => TransactionExt::TYPE_PURCHASE, 'amount' => 500.00, 'shares' => 500],
            ['type' => TransactionExt::TYPE_PURCHASE, 'amount' => 250.00, 'shares' => 250],
            ['type' => TransactionExt::TYPE_SALE, 'amount' => -100.00, 'shares' => -100],
        ];

        foreach ($transactionData as $data) {
            $transaction = $this->df->createTransaction(
                $data['amount'],
                $account,
                $data['type'],
                TransactionExt::STATUS_CLEARED
            );
            $this->assertNotNull($transaction);
            $this->assertEquals($data['type'], $transaction->type);
        }

        // Verify all transactions exist
        $transactions = TransactionExt::where('account_id', $account->id)->get();
        $this->assertCount(4, $transactions); // 1 initial + 3 additional
    }

    // ==================== DataFactory Integration ====================

    public function test_creates_fund_with_datafactory_assets()
    {
        // Create fund via setup
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'DataFactory Assets Fund',
                'portfolio_source' => 'DF_ASSETS',
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'DataFactory Assets Fund')->first();

        // Use DataFactory to create assets
        $assetSymbols = ['SPXL', 'SOXL', 'TECL', 'IAU', 'BTC'];

        foreach ($assetSymbols as $symbol) {
            $asset = $this->df->createAsset($symbol);
            $this->assertNotNull($asset);

            // Create price for asset
            $price = $this->df->createAssetPrice($asset, rand(100, 500));
            $this->assertNotNull($price);
        }

        // Verify assets created
        $this->assertCount(5, $this->df->assets);
        $this->assertCount(5, $this->df->assetPrices);
    }

    // ==================== Preview Then Create Multiple ====================

    public function test_preview_multiple_funds_then_create()
    {
        $fundConfigs = [
            ['name' => 'Preview Fund 1', 'shares' => 100, 'value' => 100.00],
            ['name' => 'Preview Fund 2', 'shares' => 200, 'value' => 400.00],
            ['name' => 'Preview Fund 3', 'shares' => 300, 'value' => 900.00],
        ];

        foreach ($fundConfigs as $index => $config) {
            // First preview
            $previewResponse = $this->actingAs($this->user)
                ->post(route('funds.storeWithSetup'), [
                    'name' => $config['name'],
                    'portfolio_source' => 'PREVIEW_' . ($index + 1),
                    'create_initial_transaction' => true,
                    'initial_shares' => $config['shares'],
                    'initial_value' => $config['value'],
                    'preview' => 1,
                ]);

            $previewResponse->assertStatus(200);
            $previewResponse->assertViewIs('funds.preview_setup');

            // Then actually create
            $createResponse = $this->actingAs($this->user)
                ->post(route('funds.storeWithSetup'), [
                    'name' => $config['name'],
                    'portfolio_source' => 'PREVIEW_' . ($index + 1),
                    'create_initial_transaction' => true,
                    'initial_shares' => $config['shares'],
                    'initial_value' => $config['value'],
                    'preview' => 0,
                ]);

            $createResponse->assertStatus(302);

            // Verify fund created
            $fund = Fund::where('name', $config['name'])->first();
            $this->assertNotNull($fund);
        }

        // Verify all 3 funds exist
        $previewFunds = Fund::where('name', 'LIKE', 'Preview Fund %')->get();
        $this->assertCount(3, $previewFunds);
    }

    // ==================== Realistic Shares/Value Scenarios ====================

    public function test_various_share_price_scenarios()
    {
        $scenarios = [
            // [name, shares, value, expected_share_price]
            ['Penny Stock Fund', 10000, 100.00, 0.01],
            ['Standard Fund', 1000, 1000.00, 1.00],
            ['High Value Fund', 100, 10000.00, 100.00],
            ['Fractional Fund', 0.5, 50.00, 100.00],
            // Note: shares column is decimal(19, 4), so max 4 decimal places
            ['Precision Fund', 123.4568, 1234.56, 9.9993],
        ];

        foreach ($scenarios as [$name, $shares, $value, $expectedPrice]) {
            $response = $this->actingAs($this->user)
                ->post(route('funds.storeWithSetup'), [
                    'name' => $name,
                    'portfolio_source' => str_replace(' ', '_', strtoupper($name)),
                    'create_initial_transaction' => true,
                    'initial_shares' => $shares,
                    'initial_value' => $value,
                    'preview' => 0,
                ]);

            $fund = FundExt::where('name', $name)->first();
            $account = $fund->account();
            $balance = AccountBalance::where('account_id', $account->id)->first();

            $this->assertNotNull($balance);
            $this->assertEquals($value, $balance->balance);
            $this->assertEquals($shares, $balance->shares);
            // Use delta for floating-point comparison due to precision (shares column is decimal(19,4))
            $this->assertEqualsWithDelta($expectedPrice, $balance->share_value, 0.001, "Share price mismatch for $name");
        }
    }

    // ==================== Error Recovery Scenarios ====================

    public function test_creates_fund_after_failed_attempt()
    {
        // First attempt - invalid (missing required field)
        $failedResponse = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Recovery Fund',
                // Missing portfolio_source - should fail
                'preview' => 0,
            ]);

        $failedResponse->assertSessionHasErrors('portfolio_source');

        // Verify fund was NOT created
        $fund = Fund::where('name', 'Recovery Fund')->first();
        $this->assertNull($fund);

        // Second attempt - valid
        $successResponse = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Recovery Fund',
                'portfolio_source' => 'RECOVERY_PORTFOLIO',
                'preview' => 0,
            ]);

        $successResponse->assertStatus(302);

        // Verify fund created on second attempt
        $fund = Fund::where('name', 'Recovery Fund')->first();
        $this->assertNotNull($fund);
    }

    // ==================== Batch Preview Scenario ====================

    public function test_preview_batch_then_create_selected()
    {
        $batchConfigs = [
            ['name' => 'Batch Fund A', 'source' => 'BATCH_A', 'create' => true],
            ['name' => 'Batch Fund B', 'source' => 'BATCH_B', 'create' => false],
            ['name' => 'Batch Fund C', 'source' => 'BATCH_C', 'create' => true],
            ['name' => 'Batch Fund D', 'source' => 'BATCH_D', 'create' => false],
            ['name' => 'Batch Fund E', 'source' => 'BATCH_E', 'create' => true],
        ];

        // Preview all
        foreach ($batchConfigs as $config) {
            $response = $this->actingAs($this->user)
                ->post(route('funds.storeWithSetup'), [
                    'name' => $config['name'],
                    'portfolio_source' => $config['source'],
                    'preview' => 1,
                ]);

            $response->assertStatus(200);
        }

        // Create only selected ones
        foreach ($batchConfigs as $config) {
            if ($config['create']) {
                $response = $this->actingAs($this->user)
                    ->post(route('funds.storeWithSetup'), [
                        'name' => $config['name'],
                        'portfolio_source' => $config['source'],
                        'preview' => 0,
                    ]);

                $response->assertStatus(302);
            }
        }

        // Verify only 3 funds created (A, C, E)
        $createdFunds = Fund::where('name', 'LIKE', 'Batch Fund %')->get();
        $this->assertCount(3, $createdFunds);

        $this->assertNotNull(Fund::where('name', 'Batch Fund A')->first());
        $this->assertNull(Fund::where('name', 'Batch Fund B')->first());
        $this->assertNotNull(Fund::where('name', 'Batch Fund C')->first());
        $this->assertNull(Fund::where('name', 'Batch Fund D')->first());
        $this->assertNotNull(Fund::where('name', 'Batch Fund E')->first());
    }

    // ==================== Existing Fund Integration ====================

    public function test_creates_new_fund_alongside_existing_datafactory_fund()
    {
        // Use DataFactory to create existing fund
        $existingFund = $this->df->createFund(5000, 5000, '2022-01-01');
        $existingFundId = $existingFund->id;

        // Create new fund via setup
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'New Setup Fund',
                'portfolio_source' => 'NEW_SETUP',
                'create_initial_transaction' => true,
                'initial_shares' => 1000,
                'initial_value' => 2000.00,
                'preview' => 0,
            ]);

        $newFund = Fund::where('name', 'New Setup Fund')->first();
        $this->assertNotNull($newFund);

        // Verify existing fund still exists and unchanged
        $existingFundCheck = Fund::find($existingFundId);
        $this->assertNotNull($existingFundCheck);

        // Verify both funds are independent
        $this->assertNotEquals($existingFund->id, $newFund->id);
    }
}
