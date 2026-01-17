<?php

namespace Tests\Feature;

use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\Portfolio;
use App\Models\TransactionExt;
use App\Models\AccountBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * API tests for Fund Setup with Complete Setup
 *
 * Tests the POST /api/funds/setup endpoint with:
 * - Dry run (preview) mode
 * - Actual creation mode
 * - Validation
 * - Edge cases
 */
class FundSetupAPITest extends TestCase
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

    // ==================== Basic Creation Tests ====================

    public function test_creates_fund_via_api_with_minimal_data()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'API Test Fund',
            'portfolio_source' => 'API_TEST',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Fund created successfully with account, portfolio, and initial transaction',
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'fund' => ['id', 'name', 'goal'],
                    'account' => ['id', 'fund_id', 'nickname', 'code'],
                    'portfolios' => [
                        ['id', 'fund_id', 'source']
                    ],
                    'transaction',
                    'account_balance',
                ],
                'message',
            ]);

        // Verify fund created
        $this->assertDatabaseHas('funds', [
            'name' => 'API Test Fund',
        ]);
    }

    public function test_creates_fund_via_api_with_complete_data()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Complete API Fund',
            'goal' => 'Testing complete fund creation via API',
            'portfolio_source' => 'COMPLETE_API',
            'account_nickname' => 'Custom API Account',
            'create_initial_transaction' => true,
            'initial_shares' => 1000,
            'initial_value' => 5000.00,
            'transaction_description' => 'API initial setup',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');

        // Verify all components
        $this->assertEquals('Complete API Fund', $data['fund']['name']);
        $this->assertEquals('Testing complete fund creation via API', $data['fund']['goal']);
        $this->assertEquals('Custom API Account', $data['account']['nickname']);
        $this->assertEquals('COMPLETE_API', $data['portfolios'][0]['source']);
        $this->assertEquals(5000.00, $data['transaction']['amount']);
        $this->assertEquals(1000, $data['transaction']['shares']);
        $this->assertEquals('API initial setup', $data['transaction']['description']);
        $this->assertEquals(5000.00, $data['account_balance']['balance']);
        $this->assertEquals(1000, $data['account_balance']['shares']);
        $this->assertEquals(5.00, $data['account_balance']['share_value']);
    }

    // ==================== Dry Run (Preview) Tests ====================

    public function test_dry_run_mode_does_not_create_entities()
    {
        $fundCountBefore = Fund::count();
        $accountCountBefore = AccountExt::count();
        $portfolioCountBefore = Portfolio::count();

        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Dry Run Fund',
            'portfolio_source' => 'DRY_RUN',
            'dry_run' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Fund setup preview generated successfully',
                'data' => [
                    'dry_run' => true,
                    'note' => 'Preview mode - no changes were saved to database',
                ],
            ]);

        // Verify no entities created
        $this->assertEquals($fundCountBefore, Fund::count());
        $this->assertEquals($accountCountBefore, AccountExt::count());
        $this->assertEquals($portfolioCountBefore, Portfolio::count());
    }

    public function test_dry_run_returns_all_entity_data()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Preview Fund',
            'goal' => 'Preview goal',
            'portfolio_source' => 'PREVIEW_SOURCE',
            'create_initial_transaction' => true,
            'initial_shares' => 500,
            'initial_value' => 2500.00,
            'dry_run' => true,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertTrue($data['dry_run']);
        $this->assertEquals('Preview Fund', $data['fund']['name']);
        $this->assertEquals('Preview goal', $data['fund']['goal']);
        $this->assertNotNull($data['account']);
        $this->assertCount(1, $data['portfolios']);
        $this->assertEquals('PREVIEW_SOURCE', $data['portfolios'][0]['source']);
        $this->assertEquals(2500.00, $data['transaction']['amount']);
        $this->assertEquals(500, $data['transaction']['shares']);
        $this->assertEquals(2500.00, $data['account_balance']['balance']);
        $this->assertEquals(5.00, $data['account_balance']['share_value']);
    }

    // ==================== Validation Tests ====================

    public function test_validates_required_fields()
    {
        $response = $this->postJson('/api/funds/setup', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'portfolio_source']);
    }

    public function test_validates_name_max_length()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => str_repeat('a', 31), // 31 characters
            'portfolio_source' => 'TEST',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validates_initial_shares_minimum()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST',
            'initial_shares' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['initial_shares']);
    }

    public function test_validates_initial_value_minimum()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST',
            'initial_value' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['initial_value']);
    }

    // ==================== Multiple Portfolio Tests ====================

    public function test_creates_fund_with_multiple_portfolios()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Multi Portfolio Fund',
            'portfolio_source' => [
                'PORTFOLIO_A',
                'PORTFOLIO_B',
                'PORTFOLIO_C',
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(3, $data['portfolios']);
        $this->assertEquals('PORTFOLIO_A', $data['portfolios'][0]['source']);
        $this->assertEquals('PORTFOLIO_B', $data['portfolios'][1]['source']);
        $this->assertEquals('PORTFOLIO_C', $data['portfolios'][2]['source']);

        // Verify in database
        $fund = Fund::where('name', 'Multi Portfolio Fund')->first();
        $this->assertCount(3, Portfolio::where('fund_id', $fund->id)->get());
    }

    // ==================== Transaction Tests ====================

    public function test_creates_fund_without_initial_transaction()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'No Transaction Fund',
            'portfolio_source' => 'NO_TRANS',
            'create_initial_transaction' => false,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertArrayNotHasKey('transaction', $data);
        $this->assertArrayNotHasKey('account_balance', $data);
    }

    public function test_creates_transaction_with_only_value()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Value Only Fund',
            'portfolio_source' => 'VALUE_ONLY',
            'create_initial_transaction' => true,
            'initial_value' => 100.00,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(100.00, $data['transaction']['amount']);
        $this->assertNotNull($data['transaction']['shares']);
    }

    public function test_creates_transaction_with_shares_and_value()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Shares and Value Fund',
            'portfolio_source' => 'SHARES_VALUE',
            'create_initial_transaction' => true,
            'initial_shares' => 250,
            'initial_value' => 1000.00,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(1000.00, $data['transaction']['amount']);
        $this->assertEquals(250, $data['transaction']['shares']);
        $this->assertEquals(4.00, $data['account_balance']['share_value']);
    }

    // ==================== Account Tests ====================

    public function test_creates_account_with_auto_generated_nickname()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Auto Nickname Fund',
            'portfolio_source' => 'AUTO_NICK',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('Auto Nickname Fund Fund Account', $data['account']['nickname']);
    }

    public function test_creates_account_with_custom_nickname()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Custom Nickname Fund',
            'portfolio_source' => 'CUSTOM_NICK',
            'account_nickname' => 'My Custom Account Name',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('My Custom Account Name', $data['account']['nickname']);
    }

    public function test_account_has_null_user_id()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Null User Fund',
            'portfolio_source' => 'NULL_USER',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNull($data['account']['user_id']);

        // Verify in database
        $account = AccountExt::find($data['account']['id']);
        $this->assertNull($account->user_id);
    }

    public function test_account_code_matches_fund_id()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Code Test Fund',
            'portfolio_source' => 'CODE_TEST',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $expectedCode = 'F' . $data['fund']['id'];
        $this->assertEquals($expectedCode, $data['account']['code']);
    }

    // ==================== Precision Tests ====================

    public function test_preserves_high_precision_shares()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Precision Fund',
            'portfolio_source' => 'PRECISION',
            'create_initial_transaction' => true,
            'initial_shares' => 123.45678901,
            'initial_value' => 1000.00,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(123.45678901, $data['transaction']['shares']);
    }

    // ==================== Edge Cases ====================

    public function test_creates_fund_with_minimal_initial_value()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Minimal Value Fund',
            'portfolio_source' => 'MINIMAL',
            'create_initial_transaction' => true,
            'initial_shares' => 1,
            'initial_value' => 0.01,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(0.01, $data['transaction']['amount']);
        $this->assertEquals(1, $data['transaction']['shares']);
        $this->assertEquals(0.01, $data['account_balance']['share_value']);
    }

    public function test_creates_fund_with_fractional_shares()
    {
        $response = $this->postJson('/api/funds/setup', [
            'name' => 'Fractional Shares Fund',
            'portfolio_source' => 'FRACTIONAL',
            'create_initial_transaction' => true,
            'initial_shares' => 0.5,
            'initial_value' => 50.00,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(0.5, $data['transaction']['shares']);
        $this->assertEquals(100.00, $data['account_balance']['share_value']);
    }

    // ==================== Sequential API Calls ====================

    public function test_creates_multiple_funds_sequentially_via_api()
    {
        $fundNames = ['API Fund 1', 'API Fund 2', 'API Fund 3'];

        foreach ($fundNames as $index => $name) {
            $response = $this->postJson('/api/funds/setup', [
                'name' => $name,
                'portfolio_source' => 'API_SEQ_' . ($index + 1),
                'create_initial_transaction' => true,
                'initial_shares' => 100 * ($index + 1),
                'initial_value' => 500 * ($index + 1),
            ]);

            $response->assertStatus(200);
        }

        // Verify all funds created
        foreach ($fundNames as $name) {
            $this->assertDatabaseHas('funds', ['name' => $name]);
        }
    }

    // ==================== Preview Then Create Pattern ====================

    public function test_preview_then_create_workflow()
    {
        // First preview
        $previewResponse = $this->postJson('/api/funds/setup', [
            'name' => 'Preview Then Create',
            'portfolio_source' => 'PREVIEW_CREATE',
            'create_initial_transaction' => true,
            'initial_shares' => 100,
            'initial_value' => 500.00,
            'dry_run' => true,
        ]);

        $previewResponse->assertStatus(200);
        $previewData = $previewResponse->json('data');
        $this->assertTrue($previewData['dry_run']);

        // Verify nothing created
        $this->assertDatabaseMissing('funds', ['name' => 'Preview Then Create']);

        // Now actually create
        $createResponse = $this->postJson('/api/funds/setup', [
            'name' => 'Preview Then Create',
            'portfolio_source' => 'PREVIEW_CREATE',
            'create_initial_transaction' => true,
            'initial_shares' => 100,
            'initial_value' => 500.00,
            'dry_run' => false,
        ]);

        $createResponse->assertStatus(200);
        $createData = $createResponse->json('data');
        $this->assertArrayNotHasKey('dry_run', $createData);

        // Verify fund created
        $this->assertDatabaseHas('funds', ['name' => 'Preview Then Create']);
    }

    // ==================== Error Handling ====================

    public function test_returns_error_on_exception()
    {
        // Try to create with invalid data that will cause a database error
        $response = $this->postJson('/api/funds/setup', [
            'name' => null, // This will pass validation but fail at database level
            'portfolio_source' => 'ERROR_TEST',
        ]);

        $response->assertStatus(200) // API returns 200 with success: false
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }
}
