<?php

namespace Tests\Feature;

use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\Portfolio;
use App\Models\TransactionExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Sequential and realistic API scenario tests for Fund Setup
 *
 * Tests complex API workflows like:
 * - Creating multiple funds via API in sequence
 * - Monarch-like setup with 16 portfolios
 * - Batch preview then selective creation
 * - Real-world data scenarios
 */
class FundSetupAPISequentialTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createUser();
        $this->user = $this->df->user;
    }

    // ==================== Monarch 16 Portfolio Scenario ====================

    public function test_creates_fund_with_16_portfolios_via_api()
    {
        $monarchSources = [
            'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX',
            'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX',
            'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX', 'MONARCH_FIDE_XXXX',
            'MONARCH_SCHW_ELA',  'MONARCH_IBKR_XXXX', 'MONARCH_IBKR_XXXX',
            'MONARCH_IBKR_XXXX', 'MONARCH_MERR_00A1', 'MONARCH_MERR_1000A',
            'MONARCH_MERR_1000B',
        ];

        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Monarch Consolidated API',
            'goal' => 'Consolidated view of all Monarch accounts via API',
            'portfolio_source' => $monarchSources,
            'create_initial_transaction' => true,
            'initial_shares' => 1,
            'initial_value' => 0.01,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify all 16 portfolios created
        $this->assertCount(16, $data['portfolios']);

        // Verify each source
        $createdSources = array_map(function ($p) {
            return $p['source'];
        }, $data['portfolios']);

        foreach ($monarchSources as $source) {
            $this->assertContains($source, $createdSources, "Missing portfolio: $source");
        }

        // Verify in database
        $fund = Fund::where('name', 'Monarch Consolidated API')->first();
        $this->assertCount(16, Portfolio::where('fund_id', $fund->id)->get());
    }

    // ==================== Sequential Fund Creation ====================

    public function test_creates_multiple_funds_sequentially_via_api()
    {
        $fundConfigs = [
            ['name' => 'API Retirement Fund', 'shares' => 100, 'value' => 1000.00],
            ['name' => 'API Education Fund', 'shares' => 200, 'value' => 2000.00],
            ['name' => 'API Emergency Fund', 'shares' => 300, 'value' => 3000.00],
            ['name' => 'API Investment Fund', 'shares' => 400, 'value' => 4000.00],
            ['name' => 'API Tax Fund', 'shares' => 500, 'value' => 5000.00],
        ];

        $createdFundIds = [];

        foreach ($fundConfigs as $index => $config) {
            $response = $this->postJson('/api/funds/setup', [
                'name' => $config['name'],
                'goal' => "Goal for {$config['name']}",
                'portfolio_source' => 'API_FUND_' . ($index + 1),
                'create_initial_transaction' => true,
                'initial_shares' => $config['shares'],
                'initial_value' => $config['value'],
            ]);

            $response->assertStatus(200);
            $data = $response->json('data');
            $createdFundIds[] = $data['fund']['id'];
        }

        // Verify all funds created
        $this->assertCount(5, $createdFundIds);

        // Verify each fund has correct structure
        foreach ($createdFundIds as $fundId) {
            $fund = Fund::find($fundId);
            $this->assertNotNull($fund);

            $account = AccountExt::where('fund_id', $fundId)->whereNull('user_id')->first();
            $this->assertNotNull($account);

            $portfolio = Portfolio::where('fund_id', $fundId)->first();
            $this->assertNotNull($portfolio);
        }
    }

    // ==================== Batch Preview Then Selective Create ====================

    public function test_batch_preview_then_selective_create_via_api()
    {
        $batchConfigs = [
            ['name' => 'API Batch A', 'source' => 'BATCH_A', 'create' => true],
            ['name' => 'API Batch B', 'source' => 'BATCH_B', 'create' => false],
            ['name' => 'API Batch C', 'source' => 'BATCH_C', 'create' => true],
            ['name' => 'API Batch D', 'source' => 'BATCH_D', 'create' => false],
            ['name' => 'API Batch E', 'source' => 'BATCH_E', 'create' => true],
        ];

        // Preview all
        foreach ($batchConfigs as $config) {
            $response = $this->postJson('/api/funds/setup', [
                'name' => $config['name'],
                'portfolio_source' => $config['source'],
                'dry_run' => true,
            ]);

            $response->assertStatus(200);
            $data = $response->json('data');
            $this->assertTrue($data['dry_run']);
        }

        // Create only selected ones
        foreach ($batchConfigs as $config) {
            if ($config['create']) {
                $response = $this->postJson('/api/funds/setup', [
                    'name' => $config['name'],
                    'portfolio_source' => $config['source'],
                    'dry_run' => false,
                ]);

                $response->assertStatus(200);
            }
        }

        // Verify only 3 funds created (A, C, E)
        $this->assertDatabaseHas('funds', ['name' => 'API Batch A']);
        $this->assertDatabaseMissing('funds', ['name' => 'API Batch B']);
        $this->assertDatabaseHas('funds', ['name' => 'API Batch C']);
        $this->assertDatabaseMissing('funds', ['name' => 'API Batch D']);
        $this->assertDatabaseHas('funds', ['name' => 'API Batch E']);
    }

    // ==================== Various Share Price Scenarios ====================

    public function test_various_share_price_scenarios_via_api()
    {
        $scenarios = [
            // [name, shares, value, expected_share_price]
            ['API Penny Stock Fund', 10000, 100.00, 0.01],
            ['API Standard Fund', 1000, 1000.00, 1.00],
            ['API High Value Fund', 100, 10000.00, 100.00],
            ['API Fractional Fund', 0.5, 50.00, 100.00],
            ['API Precision Fund', 123.45678901, 1234.56, 10.00],
        ];

        foreach ($scenarios as [$name, $shares, $value, $expectedPrice]) {
            $response = $this->postJson('/api/funds/setup', [
                'name' => $name,
                'portfolio_source' => str_replace(' ', '_', strtoupper($name)),
                'create_initial_transaction' => true,
                'initial_shares' => $shares,
                'initial_value' => $value,
            ]);

            $response->assertStatus(200);
            $data = $response->json('data');

            $this->assertEquals($value, $data['account_balance']['balance']);
            $this->assertEquals($shares, $data['account_balance']['shares']);
            $this->assertEquals($expectedPrice, $data['account_balance']['share_value'], "Share price mismatch for $name");
        }
    }

    // ==================== Multiple Portfolios in Loop ====================

    public function test_creates_fund_then_adds_portfolios_via_api()
    {
        // Create fund with first portfolio
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Multi Portfolio API Fund',
            'portfolio_source' => 'API_PORT_1',
        ]);

        $response->assertStatus(200);
        $fund = Fund::where('name', 'Multi Portfolio API Fund')->first();

        // Add 10 more portfolios
        for ($i = 2; $i <= 11; $i++) {
            $portfolio = Portfolio::create([
                'fund_id' => $fund->id,
                'source' => "API_PORT_$i",
            ]);
            $this->assertNotNull($portfolio);
        }

        // Verify total count (1 initial + 10 additional)
        $portfolios = Portfolio::where('fund_id', $fund->id)->get();
        $this->assertCount(11, $portfolios);
    }

    // ==================== Integration with DataFactory ====================

    public function test_creates_fund_alongside_existing_datafactory_fund_via_api()
    {
        // Use DataFactory to create existing fund
        $existingFund = $this->df->createFund(5000, 5000, '2022-01-01');
        $existingFundId = $existingFund->id;

        // Create new fund via API
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'New API Fund',
            'portfolio_source' => 'NEW_API_SETUP',
            'create_initial_transaction' => true,
            'initial_shares' => 1000,
            'initial_value' => 2000.00,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify existing fund still exists and unchanged
        $existingFundCheck = Fund::find($existingFundId);
        $this->assertNotNull($existingFundCheck);

        // Verify both funds are independent
        $this->assertNotEquals($existingFundId, $data['fund']['id']);
    }

    // ==================== Complex Transaction Scenarios ====================

    public function test_creates_fund_then_multiple_transactions_via_api()
    {
        // Create fund
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Sequential Transaction API Fund',
            'portfolio_source' => 'SEQ_TRANS_API',
            'create_initial_transaction' => true,
            'initial_shares' => 1000,
            'initial_value' => 1000.00,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $fund = Fund::find($data['fund']['id']);
        $account = $fund->account();

        // Create additional transactions via DataFactory
        $transactionData = [
            ['type' => TransactionExt::TYPE_PURCHASE, 'amount' => 500.00, 'shares' => 500],
            ['type' => TransactionExt::TYPE_PURCHASE, 'amount' => 250.00, 'shares' => 250],
            ['type' => TransactionExt::TYPE_SALE, 'amount' => -100.00, 'shares' => -100],
        ];

        foreach ($transactionData as $tData) {
            $transaction = $this->df->createTransaction(
                $tData['amount'],
                $account,
                $tData['type'],
                TransactionExt::STATUS_CLEARED
            );
            $this->assertNotNull($transaction);
        }

        // Verify all transactions exist
        $transactions = TransactionExt::where('account_id', $account->id)->get();
        $this->assertCount(4, $transactions); // 1 initial + 3 additional
    }

    // ==================== Automation Script Simulation ====================

    public function test_simulates_automated_fund_creation_script()
    {
        // Simulate a script that creates multiple funds with different configurations
        $script_configs = [
            [
                'name' => 'Auto Fund 1',
                'portfolios' => ['AUTO_1A', 'AUTO_1B'],
                'shares' => 100,
                'value' => 1000.00,
            ],
            [
                'name' => 'Auto Fund 2',
                'portfolios' => ['AUTO_2A', 'AUTO_2B', 'AUTO_2C'],
                'shares' => 200,
                'value' => 4000.00,
            ],
            [
                'name' => 'Auto Fund 3',
                'portfolios' => ['AUTO_3A'],
                'shares' => 50,
                'value' => 500.00,
            ],
        ];

        foreach ($script_configs as $config) {
            $response = $this->postJson('/api/funds/setup', [
                'name' => $config['name'],
                'portfolio_source' => $config['portfolios'],
                'create_initial_transaction' => true,
                'initial_shares' => $config['shares'],
                'initial_value' => $config['value'],
            ]);

            $response->assertStatus(200);
            $data = $response->json('data');

            // Verify correct number of portfolios
            $this->assertCount(count($config['portfolios']), $data['portfolios']);
        }

        // Verify all 3 funds created
        $this->assertEquals(3, Fund::where('name', 'LIKE', 'Auto Fund %')->count());
    }

    // ==================== Preview All Create One Pattern ====================

    public function test_preview_multiple_create_best_option_via_api()
    {
        $options = [
            ['name' => 'Option A', 'shares' => 100, 'value' => 1000.00],
            ['name' => 'Option B', 'shares' => 500, 'value' => 5000.00],
            ['name' => 'Option C', 'shares' => 1000, 'value' => 10000.00],
        ];

        $previewResults = [];

        // Preview all options
        foreach ($options as $index => $option) {
            $response = $this->postJson('/api/funds/setup', [
                'name' => $option['name'],
                'portfolio_source' => 'OPTION_' . chr(65 + $index), // A, B, C
                'create_initial_transaction' => true,
                'initial_shares' => $option['shares'],
                'initial_value' => $option['value'],
                'dry_run' => true,
            ]);

            $response->assertStatus(200);
            $previewResults[] = $response->json('data');
        }

        // Verify all previews completed without creating
        $this->assertCount(3, $previewResults);
        $this->assertDatabaseMissing('funds', ['name' => 'Option A']);
        $this->assertDatabaseMissing('funds', ['name' => 'Option B']);
        $this->assertDatabaseMissing('funds', ['name' => 'Option C']);

        // Now create "best" option (Option B)
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Option B',
            'portfolio_source' => 'OPTION_B',
            'create_initial_transaction' => true,
            'initial_shares' => 500,
            'initial_value' => 5000.00,
            'dry_run' => false,
        ]);

        $response->assertStatus(200);

        // Verify only Option B created
        $this->assertDatabaseMissing('funds', ['name' => 'Option A']);
        $this->assertDatabaseHas('funds', ['name' => 'Option B']);
        $this->assertDatabaseMissing('funds', ['name' => 'Option C']);
    }
}
