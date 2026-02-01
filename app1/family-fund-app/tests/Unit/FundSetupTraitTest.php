<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\FundSetupTrait;
use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\Portfolio;
use App\Models\TransactionExt;
use App\Repositories\FundRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for FundSetupTrait
 *
 * Tests the setupFund() method in isolation with various scenarios
 */
class FundSetupTraitTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected FundSetupTraitTestClass $traitInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();

        // Create instance of test class that uses the trait
        $this->traitInstance = new FundSetupTraitTestClass(
            app(FundRepository::class),
            app(TransactionRepository::class)
        );
    }

    // ==================== Dry Run Mode Tests ====================

    public function test_setup_fund_dry_run_does_not_persist_changes()
    {
        $fundCountBefore = Fund::count();
        $accountCountBefore = AccountExt::count();
        $portfolioCountBefore = Portfolio::count();

        $input = [
            'name' => 'Dry Run Test',
            'goal' => 'Testing dry run',
            'portfolio_source' => 'DRY_RUN_TEST',
            'create_initial_transaction' => true,
            'initial_shares' => 100,
            'initial_value' => 100.00,
        ];

        $result = $this->traitInstance->setupFund($input, true); // dry_run = true

        // Should return data
        $this->assertArrayHasKey('fund', $result);
        $this->assertArrayHasKey('account', $result);
        $this->assertArrayHasKey('portfolios', $result);
        $this->assertArrayHasKey('transaction', $result);

        // But should not persist to database
        $this->assertEquals($fundCountBefore, Fund::count());
        $this->assertEquals($accountCountBefore, AccountExt::count());
        $this->assertEquals($portfolioCountBefore, Portfolio::count());
    }

    public function test_setup_fund_dry_run_returns_all_entity_data()
    {
        $input = [
            'name' => 'Data Return Test',
            'goal' => 'Test data return',
            'portfolio_source' => 'DATA_RETURN_TEST',
            'create_initial_transaction' => true,
            'initial_shares' => 500,
            'initial_value' => 1000.00,
            'transaction_description' => 'Test transaction',
        ];

        $result = $this->traitInstance->setupFund($input, true);

        // Verify fund data
        $this->assertNotNull($result['fund']);
        $this->assertEquals('Data Return Test', $result['fund']->name);
        $this->assertEquals('Test data return', $result['fund']->goal);

        // Verify account data
        $this->assertNotNull($result['account']);
        $this->assertNull($result['account']->user_id);
        $this->assertEquals($result['fund']->id, $result['account']->fund_id);

        // Verify portfolio data
        $this->assertIsArray($result['portfolios']);
        $this->assertCount(1, $result['portfolios']);
        $this->assertEquals('DATA_RETURN_TEST', $result['portfolios'][0]->source);

        // Verify transaction data
        $this->assertNotNull($result['transaction']);
        $this->assertEquals(1000.00, $result['transaction']->amount);
        $this->assertEquals(500, $result['transaction']->shares);
        $this->assertEquals('Test transaction', $result['transaction']->description);
    }

    // ==================== Actual Creation Mode Tests ====================

    public function test_setup_fund_creates_all_entities()
    {
        $input = [
            'name' => 'Creation Test',
            'portfolio_source' => 'CREATION_TEST',
            'create_initial_transaction' => false,
        ];

        $result = $this->traitInstance->setupFund($input, false); // dry_run = false

        // Verify entities were persisted
        $fund = Fund::find($result['fund']->id);
        $this->assertNotNull($fund);
        $this->assertEquals('Creation Test', $fund->name);

        $account = AccountExt::find($result['account']->id);
        $this->assertNotNull($account);

        $portfolio = Portfolio::find($result['portfolios'][0]->id);
        $this->assertNotNull($portfolio);
    }

    // ==================== Account Creation Tests ====================

    public function test_creates_account_with_custom_nickname()
    {
        $input = [
            'name' => 'Custom Nickname Fund',
            'account_nickname' => 'My Custom Account',
            'portfolio_source' => 'CUSTOM_ACCOUNT',
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertEquals('My Custom Account', $result['account']->nickname);
    }

    public function test_creates_account_with_auto_generated_nickname()
    {
        $input = [
            'name' => 'Auto Generated Fund',
            'portfolio_source' => 'AUTO_GEN',
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertEquals('Auto Generated Fund Fund Account', $result['account']->nickname);
    }

    public function test_creates_account_with_correct_code()
    {
        $input = [
            'name' => 'Code Test Fund',
            'portfolio_source' => 'CODE_TEST',
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $expectedCode = 'F' . $result['fund']->id;
        $this->assertEquals($expectedCode, $result['account']->code);
    }

    public function test_creates_account_with_null_user_id()
    {
        $input = [
            'name' => 'User ID Test',
            'portfolio_source' => 'USER_ID_TEST',
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertNull($result['account']->user_id);
    }

    // ==================== Portfolio Creation Tests ====================

    public function test_creates_single_portfolio()
    {
        $input = [
            'name' => 'Single Portfolio Fund',
            'portfolio_source' => 'SINGLE_PORTFOLIO',
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertIsArray($result['portfolios']);
        $this->assertCount(1, $result['portfolios']);
        $this->assertEquals('SINGLE_PORTFOLIO', $result['portfolios'][0]->source);
    }

    public function test_creates_multiple_portfolios_from_array()
    {
        $input = [
            'name' => 'Multiple Portfolio Fund',
            'portfolio_source' => [
                'PORTFOLIO_1',
                'PORTFOLIO_2',
                'PORTFOLIO_3',
            ],
            // Skip transaction creation since FundExt::portfolio() expects exactly 1 portfolio
            // and transaction processing calls fund->valueAsOf() which calls portfolio()
            'create_initial_transaction' => false,
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertIsArray($result['portfolios']);
        $this->assertCount(3, $result['portfolios']);
        $this->assertEquals('PORTFOLIO_1', $result['portfolios'][0]->source);
        $this->assertEquals('PORTFOLIO_2', $result['portfolios'][1]->source);
        $this->assertEquals('PORTFOLIO_3', $result['portfolios'][2]->source);
    }

    // ==================== Transaction Creation Tests ====================

    public function test_creates_transaction_with_shares_and_value()
    {
        $input = [
            'name' => 'Transaction Test',
            'portfolio_source' => 'TRANS_TEST',
            'create_initial_transaction' => true,
            'initial_shares' => 1000,
            'initial_value' => 5000.00,
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertNotNull($result['transaction']);
        $this->assertEquals(TransactionExt::TYPE_INITIAL, $result['transaction']->type);
        $this->assertEquals(5000.00, $result['transaction']->amount);
        $this->assertEquals(1000, $result['transaction']->shares);
    }

    public function test_creates_transaction_with_custom_description()
    {
        $input = [
            'name' => 'Description Test',
            'portfolio_source' => 'DESC_TEST',
            'create_initial_transaction' => true,
            'initial_value' => 0.01,
            'transaction_description' => 'Custom description here',
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertEquals('Custom description here', $result['transaction']->description);
    }

    public function test_uses_default_description_when_not_provided()
    {
        $input = [
            'name' => 'Default Description Test',
            'portfolio_source' => 'DEFAULT_DESC',
            'create_initial_transaction' => true,
            'initial_value' => 0.01,
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertEquals('Initial fund setup', $result['transaction']->description);
    }

    public function test_skips_transaction_when_flag_is_false()
    {
        $input = [
            'name' => 'No Transaction Test',
            'portfolio_source' => 'NO_TRANS',
            'create_initial_transaction' => false,
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertNull($result['transaction']);
        $this->assertNull($result['accountBalance']);
    }

    // ==================== Account Balance Tests ====================

    public function test_creates_account_balance_when_transaction_processed()
    {
        $input = [
            'name' => 'Balance Test',
            'portfolio_source' => 'BALANCE_TEST',
            'create_initial_transaction' => true,
            'initial_shares' => 100,
            'initial_value' => 500.00,
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertNotNull($result['accountBalance']);
        $this->assertEquals(500.00, $result['accountBalance']->balance);
        $this->assertEquals(100, $result['accountBalance']->shares);
        $this->assertEquals(5.00, $result['accountBalance']->share_value);
    }

    // ==================== Error Handling Tests ====================

    public function test_rolls_back_on_error_during_creation()
    {
        $fundCountBefore = Fund::count();

        // Try to create with invalid data that will cause an error
        // (assuming validation passes but database constraint fails)
        $input = [
            'name' => null, // Invalid - will cause database error
            'portfolio_source' => 'ERROR_TEST',
        ];

        try {
            $this->traitInstance->setupFund($input, false);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Exception expected
        }

        // Verify rollback - no new fund created
        $this->assertEquals($fundCountBefore, Fund::count());
    }

    // ==================== Integration Tests ====================

    public function test_fund_account_and_portfolio_are_linked()
    {
        $input = [
            'name' => 'Linked Entities Test',
            'portfolio_source' => 'LINKED_TEST',
        ];

        $result = $this->traitInstance->setupFund($input, false);

        // Verify relationships
        $this->assertEquals($result['fund']->id, $result['account']->fund_id);
        $this->assertEquals($result['fund']->id, $result['portfolios'][0]->fund_id);
    }

    public function test_transaction_is_linked_to_account()
    {
        $input = [
            'name' => 'Transaction Link Test',
            'portfolio_source' => 'TRANS_LINK',
            'create_initial_transaction' => true,
            'initial_value' => 100.00,
        ];

        $result = $this->traitInstance->setupFund($input, false);

        $this->assertEquals($result['account']->id, $result['transaction']->account_id);
        // Verify transaction is linked to fund through the account
        $this->assertEquals($result['fund']->id, $result['transaction']->account->fund_id);
    }

    // ==================== Shares Precision Tests ====================

    public function test_preserves_high_precision_shares()
    {
        $input = [
            'name' => 'Precision Test',
            'portfolio_source' => 'PRECISION',
            'create_initial_transaction' => true,
            'initial_shares' => 123.45678901,
            'initial_value' => 1000.00,
        ];

        $result = $this->traitInstance->setupFund($input, false);

        // Should preserve up to 4 decimal places (database precision: decimal(19,4))
        $this->assertEquals(123.4568, $result['transaction']->shares);
    }
}

/**
 * Test class to expose the protected setupFund method
 */
class FundSetupTraitTestClass
{
    use FundSetupTrait {
        setupFund as public;
    }

    protected $fundRepository;
    protected $transactionRepository;

    public function __construct($fundRepo, $transactionRepo)
    {
        $this->fundRepository = $fundRepo;
        $this->transactionRepository = $transactionRepo;
    }
}
