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
 * Feature tests for Fund Setup with Preview functionality
 * Tests the new createWithSetup/storeWithSetup flow
 *
 * Coverage:
 * - Fund creation with account, portfolio, and initial transaction
 * - Preview mode (dry run)
 * - Actual creation mode
 * - Shares and value handling
 * - Validation
 */
class FundSetupTest extends TestCase
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

    // ==================== Form Display Tests ====================

    public function test_create_with_setup_form_displays()
    {
        $response = $this->actingAs($this->user)
            ->get(route('funds.createWithSetup'));

        $response->assertStatus(200);
        $response->assertViewIs('funds.create_with_setup');
        $response->assertSee('Create Fund with Complete Setup', false);
        $response->assertSee('name', false);
        $response->assertSee('goal', false);
        $response->assertSee('portfolio_source', false);
        $response->assertSee('initial_shares', false);
        $response->assertSee('initial_value', false);
    }

    // ==================== Preview Mode Tests ====================

    public function test_preview_shows_all_entities_without_creating_them()
    {
        $fundCountBefore = Fund::count();
        $accountCountBefore = AccountExt::count();
        $portfolioCountBefore = Portfolio::count();
        $transactionCountBefore = TransactionExt::count();

        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Test Fund',
                'goal' => 'Test Goal',
                'portfolio_source' => 'TEST_SOURCE_001',
                'create_initial_transaction' => true,
                'initial_shares' => 1000,
                'initial_value' => 1000.00,
                'transaction_description' => 'Initial setup',
                'preview' => 1, // Preview mode
            ]);

        $response->assertStatus(200);
        $response->assertViewIs('funds.preview_setup');
        $response->assertViewHas('preview');
        $response->assertViewHas('input');

        // Verify NO entities were created (dry run)
        $this->assertEquals($fundCountBefore, Fund::count());
        $this->assertEquals($accountCountBefore, AccountExt::count());
        $this->assertEquals($portfolioCountBefore, Portfolio::count());
        $this->assertEquals($transactionCountBefore, TransactionExt::count());
    }

    public function test_preview_shows_fund_details()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Preview Test Fund',
                'goal' => 'Preview Test Goal',
                'portfolio_source' => 'PREVIEW_SOURCE',
                'preview' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertSee('Preview Test Fund', false);
        $response->assertSee('Preview Test Goal', false);
        $response->assertSee('PREVIEW_SOURCE', false);
    }

    public function test_preview_shows_transaction_with_shares_and_value()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Shares Test Fund',
                'portfolio_source' => 'SHARES_TEST',
                'create_initial_transaction' => true,
                'initial_shares' => 500.12345678,
                'initial_value' => 2500.50,
                'preview' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertSee('2,500.50', false); // Value formatted
        $response->assertSee('500.12345678', false); // Shares with precision
    }

    public function test_preview_shows_account_balance_details()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Balance Test Fund',
                'portfolio_source' => 'BALANCE_TEST',
                'create_initial_transaction' => true,
                'initial_shares' => 100,
                'initial_value' => 100.00,
                'preview' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertSee('Account Balance', false);
        $response->assertSee('100.00', false); // Balance
        $response->assertSee('1.00', false); // Share value
    }

    // ==================== Actual Creation Tests ====================

    public function test_creates_fund_with_all_entities()
    {
        $fundCountBefore = Fund::count();

        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Complete Test Fund',
                'goal' => 'Full setup test',
                'portfolio_source' => 'COMPLETE_TEST',
                'create_initial_transaction' => true,
                'initial_shares' => 1000,
                'initial_value' => 1000.00,
                'transaction_description' => 'Initial transaction',
                'preview' => 0, // Actual creation
            ]);

        // Should redirect to fund show page
        $response->assertStatus(302);
        $response->assertSessionHas('flash_notification');

        // Verify fund was created
        $this->assertEquals($fundCountBefore + 1, Fund::count());

        $fund = Fund::where('name', 'Complete Test Fund')->first();
        $this->assertNotNull($fund);
        $this->assertEquals('Full setup test', $fund->goal);
    }

    public function test_creates_fund_account_with_null_user_id()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Account Test Fund',
                'portfolio_source' => 'ACCOUNT_TEST',
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Account Test Fund')->first();
        $this->assertNotNull($fund);

        // Fund should have exactly 1 account with user_id=null
        $fundAccount = AccountExt::where('fund_id', $fund->id)
            ->whereNull('user_id')
            ->first();

        $this->assertNotNull($fundAccount);
        $this->assertNull($fundAccount->user_id);
        $this->assertEquals('F' . $fund->id, $fundAccount->code);
    }

    public function test_creates_portfolio_with_correct_source()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Portfolio Test Fund',
                'portfolio_source' => 'PORTFOLIO_TEST_123',
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Portfolio Test Fund')->first();
        $this->assertNotNull($fund);

        $portfolio = Portfolio::where('fund_id', $fund->id)
            ->where('source', 'PORTFOLIO_TEST_123')
            ->first();

        $this->assertNotNull($portfolio);
        $this->assertEquals('PORTFOLIO_TEST_123', $portfolio->source);
    }

    public function test_creates_initial_transaction_when_requested()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Transaction Test Fund',
                'portfolio_source' => 'TRANSACTION_TEST',
                'create_initial_transaction' => true,
                'initial_shares' => 100,
                'initial_value' => 500.00,
                'transaction_description' => 'Test transaction',
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Transaction Test Fund')->first();
        $account = AccountExt::where('fund_id', $fund->id)
            ->whereNull('user_id')
            ->first();

        $transaction = TransactionExt::where('account_id', $account->id)
            ->where('type', TransactionExt::TYPE_INITIAL)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(500.00, $transaction->amount);
        $this->assertEquals(100, $transaction->shares);
        $this->assertEquals('Test transaction', $transaction->description);
    }

    public function test_processes_transaction_and_creates_account_balance()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Balance Creation Fund',
                'portfolio_source' => 'BALANCE_CREATION',
                'create_initial_transaction' => true,
                'initial_shares' => 200,
                'initial_value' => 1000.00,
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Balance Creation Fund')->first();
        $account = AccountExt::where('fund_id', $fund->id)
            ->whereNull('user_id')
            ->first();

        $balance = AccountBalance::where('account_id', $account->id)->first();

        $this->assertNotNull($balance);
        $this->assertEquals(1000.00, $balance->balance);
        $this->assertEquals(200, $balance->shares);
        $this->assertEquals(5.00, $balance->share_value); // 1000 / 200 = 5
    }

    // ==================== Shares and Value Scenarios ====================

    public function test_creates_with_both_shares_and_value()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Shares and Value Fund',
                'portfolio_source' => 'SHARES_VALUE',
                'create_initial_transaction' => true,
                'initial_shares' => 1000,
                'initial_value' => 2500.00,
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Shares and Value Fund')->first();
        $account = $fund->account();
        $transaction = TransactionExt::where('account_id', $account->id)->first();

        $this->assertEquals(2500.00, $transaction->amount);
        $this->assertEquals(1000, $transaction->shares);

        $balance = AccountBalance::where('account_id', $account->id)->first();
        $this->assertEquals(2.50, $balance->share_value); // 2500 / 1000 = 2.5
    }

    public function test_creates_with_only_value_no_shares()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Value Only Fund',
                'portfolio_source' => 'VALUE_ONLY',
                'create_initial_transaction' => true,
                'initial_value' => 0.01,
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Value Only Fund')->first();
        $account = $fund->account();
        $transaction = TransactionExt::where('account_id', $account->id)->first();

        $this->assertEquals(0.01, $transaction->amount);
        // Shares should be calculated by processPending()
        $this->assertNotNull($transaction->shares);
    }

    public function test_creates_minimal_fund_setup()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Minimal Fund',
                'portfolio_source' => 'MINIMAL',
                'create_initial_transaction' => true,
                'initial_shares' => 1,
                'initial_value' => 0.01,
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Minimal Fund')->first();
        $account = $fund->account();
        $balance = AccountBalance::where('account_id', $account->id)->first();

        $this->assertEquals(0.01, $balance->balance);
        $this->assertEquals(1, $balance->shares);
        $this->assertEquals(0.01, $balance->share_value);
    }

    // ==================== Custom Nickname Tests ====================

    public function test_uses_custom_account_nickname()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Nickname Test',
                'account_nickname' => 'Custom Account Nickname',
                'portfolio_source' => 'NICKNAME_TEST',
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Nickname Test')->first();
        $account = AccountExt::where('fund_id', $fund->id)
            ->whereNull('user_id')
            ->first();

        $this->assertEquals('Custom Account Nickname', $account->nickname);
    }

    public function test_auto_generates_account_nickname_when_not_provided()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Auto Nickname Fund',
                'portfolio_source' => 'AUTO_NICKNAME',
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Auto Nickname Fund')->first();
        $account = AccountExt::where('fund_id', $fund->id)
            ->whereNull('user_id')
            ->first();

        $this->assertEquals('Auto Nickname Fund Fund Account', $account->nickname);
    }

    // ==================== Transaction Optional Tests ====================

    public function test_skips_transaction_when_not_requested()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'No Transaction Fund',
                'portfolio_source' => 'NO_TRANS',
                'create_initial_transaction' => false,
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'No Transaction Fund')->first();
        $account = AccountExt::where('fund_id', $fund->id)
            ->whereNull('user_id')
            ->first();

        $transactionCount = TransactionExt::where('account_id', $account->id)->count();
        $this->assertEquals(0, $transactionCount);
    }

    // ==================== Validation Tests ====================

    public function test_requires_fund_name()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'portfolio_source' => 'VALIDATION_TEST',
                'preview' => 0,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_requires_portfolio_source()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Validation Test',
                'preview' => 0,
            ]);

        $response->assertSessionHasErrors('portfolio_source');
    }

    public function test_validates_fund_name_max_length()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => str_repeat('a', 31), // Max is 30
                'portfolio_source' => 'VALIDATION_TEST',
                'preview' => 0,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_validates_portfolio_source_max_length()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Validation Test',
                'portfolio_source' => str_repeat('a', 31), // Max is 30
                'preview' => 0,
            ]);

        $response->assertSessionHasErrors('portfolio_source');
    }

    public function test_validates_initial_shares_minimum()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Share Validation',
                'portfolio_source' => 'SHARE_VALID',
                'create_initial_transaction' => true,
                'initial_shares' => -1, // Must be positive
                'initial_value' => 100,
                'preview' => 0,
            ]);

        $response->assertSessionHasErrors('initial_shares');
    }

    public function test_validates_initial_value_minimum()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Value Validation',
                'portfolio_source' => 'VALUE_VALID',
                'create_initial_transaction' => true,
                'initial_value' => -0.01, // Must be positive
                'preview' => 0,
            ]);

        $response->assertSessionHasErrors('initial_value');
    }

    public function test_validates_initial_shares_is_numeric()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Numeric Validation',
                'portfolio_source' => 'NUMERIC_VALID',
                'create_initial_transaction' => true,
                'initial_shares' => 'not-a-number',
                'initial_value' => 100,
                'preview' => 0,
            ]);

        $response->assertSessionHasErrors('initial_shares');
    }

    // ==================== Redirect and Flash Tests ====================

    public function test_redirects_to_fund_show_on_success()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Redirect Test Fund',
                'portfolio_source' => 'REDIRECT_TEST',
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Redirect Test Fund')->first();
        $response->assertRedirect(route('funds.show', $fund->id));
    }

    public function test_shows_success_flash_message()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Flash Test Fund',
                'portfolio_source' => 'FLASH_TEST',
                'preview' => 0,
            ]);

        $response->assertSessionHas('flash_notification');

        // Follow the redirect to check the flash message is displayed
        $fund = Fund::where('name', 'Flash Test Fund')->first();
        $showResponse = $this->actingAs($this->user)
            ->get(route('funds.show', $fund->id));

        $showResponse->assertSee('Fund created successfully with account, portfolio, and initial transaction!');
    }

    // ==================== Edge Cases ====================

    public function test_handles_high_precision_shares()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Precision Test',
                'portfolio_source' => 'PRECISION_TEST',
                'create_initial_transaction' => true,
                'initial_shares' => 123.45678901,
                'initial_value' => 1234.56,
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', 'Precision Test')->first();
        $account = $fund->account();
        $transaction = TransactionExt::where('account_id', $account->id)->first();

        // Database stores shares as decimal(19,4), so precision is limited to 4 decimal places
        $this->assertEquals(123.4568, $transaction->shares);
    }

    public function test_handles_special_characters_in_fund_name()
    {
        $response = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => "Test's Fund & Portfolio",
                'portfolio_source' => 'SPECIAL_CHARS',
                'preview' => 0,
            ]);

        $response->assertStatus(302);

        $fund = Fund::where('name', "Test's Fund & Portfolio")->first();
        $this->assertNotNull($fund);
        $this->assertEquals("Test's Fund & Portfolio", $fund->name);
    }
}
