<?php

namespace Tests\Feature;

use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\FundExt;
use App\Models\Portfolio;
use App\Models\TransactionExt;
use App\Models\User;
use App\Models\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Integration tests verifying funds created via storeWithSetup
 * work correctly with existing FamilyFund workflows
 *
 * Tests that after fund creation:
 * - Fund show page renders
 * - New accounts can be created
 * - New transactions can be created
 * - All data displays correctly
 * - Existing workflows function properly
 */
class FundSetupWorkflowIntegrationTest extends TestCase
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

        Mail::fake();
    }

    // ==================== Fund Show Page Tests ====================

    public function test_fund_show_page_renders_after_creation()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Show Test Fund',
                'goal' => 'Test fund show page',
                'portfolio_source' => 'SHOW_TEST',
                'create_initial_transaction' => true,
                'initial_shares' => 1000,
                'initial_value' => 5000.00,
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Show Test Fund')->first();

        // Verify fund show page renders
        $response = $this->actingAs($this->user)
            ->get(route('funds.show', $fund->id));

        $response->assertStatus(200);
        $response->assertViewIs('funds.show');
        $response->assertViewHas('fund');
        $response->assertSee('Show Test Fund');
        $response->assertSee('Test fund show page');
    }

    public function test_fund_index_displays_newly_created_fund()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Index Test Fund',
                'portfolio_source' => 'INDEX_TEST',
                'preview' => 0,
            ]);

        // Verify fund appears in index
        $response = $this->actingAs($this->user)
            ->get(route('funds.index'));

        $response->assertStatus(200);
        $response->assertSee('Index Test Fund');
    }

    public function test_fund_edit_page_renders()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Edit Test Fund',
                'portfolio_source' => 'EDIT_TEST',
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Edit Test Fund')->first();

        // Verify edit page renders
        $response = $this->actingAs($this->user)
            ->get(route('funds.edit', $fund->id));

        $response->assertStatus(200);
        $response->assertViewIs('funds.edit');
        $response->assertSee('Edit Test Fund');
    }

    public function test_fund_can_be_updated_after_creation()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Update Test Fund',
                'goal' => 'Original goal',
                'portfolio_source' => 'UPDATE_TEST',
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Update Test Fund')->first();

        // Update the fund
        $response = $this->actingAs($this->user)
            ->put(route('funds.update', $fund->id), [
                'name' => 'Update Test Fund',
                'goal' => 'Updated goal',
            ]);

        $response->assertStatus(302);

        // Verify update
        $fund->refresh();
        $this->assertEquals('Updated goal', $fund->goal);
    }

    // ==================== Account Creation Tests ====================

    public function test_can_create_additional_user_account_for_fund()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Account Test Fund',
                'portfolio_source' => 'ACCOUNT_TEST',
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Account Test Fund')->first();

        // Create additional user account for this fund
        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'fund_id' => $fund->id,
                'user_id' => $this->user->id,
                'nickname' => 'My Investment Account',
            ]);

        $response->assertStatus(302);

        // Verify account created
        $account = AccountExt::where('fund_id', $fund->id)
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNotNull($account);
        $this->assertEquals('My Investment Account', $account->nickname);

        // Verify fund now has 2 accounts (1 fund account + 1 user account)
        $this->assertEquals(2, AccountExt::where('fund_id', $fund->id)->count());
    }

    public function test_accounts_index_shows_fund_accounts()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Account Index Fund',
                'account_nickname' => 'Custom Fund Account',
                'portfolio_source' => 'ACCOUNT_INDEX',
                'preview' => 0,
            ]);

        // Verify accounts index shows fund account
        $response = $this->actingAs($this->user)
            ->get(route('accounts.index'));

        $response->assertStatus(200);
        $response->assertSee('Custom Fund Account');
    }

    public function test_account_show_page_renders_for_fund_account()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Account Show Fund',
                'portfolio_source' => 'ACCOUNT_SHOW',
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Account Show Fund')->first();
        $account = $fund->account();

        // Verify account show page renders
        $response = $this->actingAs($this->user)
            ->get(route('accounts.show', $account->id));

        $response->assertStatus(200);
        $response->assertViewIs('accounts.show');
    }

    // ==================== Transaction Creation Tests ====================

    public function test_can_create_purchase_transaction_for_fund()
    {
        // Create fund via setup with initial transaction
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Transaction Test Fund',
                'portfolio_source' => 'TRANS_TEST',
                'create_initial_transaction' => true,
                'initial_shares' => 1000,
                'initial_value' => 1000.00,
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Transaction Test Fund')->first();

        // Create user account
        $userAccount = Account::create([
            'fund_id' => $fund->id,
            'user_id' => $this->user->id,
            'nickname' => 'User Account',
        ]);

        // Create purchase transaction via preview flow
        $previewResponse = $this->actingAs($this->user)
            ->post(route('transactions.preview'), [
                'account_id' => $userAccount->id,
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_PENDING,
                'value' => 500.00,
                'timestamp' => now()->format('Y-m-d'),
            ]);

        $previewResponse->assertStatus(200);

        // Store the transaction
        $storeResponse = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'account_id' => $userAccount->id,
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_PENDING,
                'value' => 500.00,
                'timestamp' => now()->format('Y-m-d'),
            ]);

        $storeResponse->assertStatus(302);

        // Verify transaction created
        $transaction = TransactionExt::where('account_id', $userAccount->id)
            ->where('type', TransactionExt::TYPE_PURCHASE)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(500.00, $transaction->value);
    }

    public function test_transactions_index_shows_fund_transactions()
    {
        // Create fund via setup with transaction
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Trans Index Fund',
                'portfolio_source' => 'TRANS_INDEX',
                'create_initial_transaction' => true,
                'initial_value' => 100.00,
                'transaction_description' => 'Initial setup transaction',
                'preview' => 0,
            ]);

        // Verify transactions index shows transaction
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index'));

        $response->assertStatus(200);
        $response->assertSee('Initial setup transaction');
    }

    public function test_transaction_show_page_renders()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Trans Show Fund',
                'portfolio_source' => 'TRANS_SHOW',
                'create_initial_transaction' => true,
                'initial_value' => 100.00,
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Trans Show Fund')->first();
        $account = $fund->account();
        $transaction = TransactionExt::where('account_id', $account->id)->first();

        // Verify transaction show page renders
        $response = $this->actingAs($this->user)
            ->get(route('transactions.show', $transaction->id));

        $response->assertStatus(200);
        $response->assertViewIs('transactions.show');
    }

    // ==================== Portfolio Tests ====================

    public function test_portfolio_index_shows_fund_portfolio()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Portfolio Index Fund',
                'portfolio_source' => 'PORTFOLIO_INDEX_TEST',
                'preview' => 0,
            ]);

        // Verify portfolios index shows portfolio
        $response = $this->actingAs($this->user)
            ->get(route('portfolios.index'));

        $response->assertStatus(200);
        $response->assertSee('PORTFOLIO_INDEX_TEST');
    }

    public function test_portfolio_show_page_renders()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Portfolio Show Fund',
                'portfolio_source' => 'PORTFOLIO_SHOW',
                'preview' => 0,
            ]);

        $portfolio = Portfolio::where('source', 'PORTFOLIO_SHOW')->first();

        // Verify portfolio show page renders
        $response = $this->actingAs($this->user)
            ->get(route('portfolios.show', $portfolio->id));

        $response->assertStatus(200);
        $response->assertViewIs('portfolios.show');
    }

    public function test_can_create_additional_portfolio_for_fund()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Multi Portfolio Fund',
                'portfolio_source' => 'PORTFOLIO_1',
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Multi Portfolio Fund')->first();

        // Create additional portfolio
        $response = $this->actingAs($this->user)
            ->post(route('portfolios.store'), [
                'fund_id' => $fund->id,
                'source' => 'PORTFOLIO_2',
            ]);

        $response->assertStatus(302);

        // Verify both portfolios exist
        $portfolios = Portfolio::where('fund_id', $fund->id)->get();
        $this->assertCount(2, $portfolios);
    }

    // ==================== Complete Workflow Test ====================

    public function test_complete_fund_lifecycle_workflow()
    {
        // 1. Create fund via setup
        $setupResponse = $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Complete Workflow Fund',
                'goal' => 'Testing complete lifecycle',
                'portfolio_source' => 'WORKFLOW_PORTFOLIO',
                'create_initial_transaction' => true,
                'initial_shares' => 1000,
                'initial_value' => 10000.00,
                'transaction_description' => 'Fund initialization',
                'preview' => 0,
            ]);

        $setupResponse->assertStatus(302);
        $fund = Fund::where('name', 'Complete Workflow Fund')->first();

        // 2. Verify fund show page
        $showResponse = $this->actingAs($this->user)
            ->get(route('funds.show', $fund->id));
        $showResponse->assertStatus(200);

        // 3. Create user account
        $accountResponse = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'fund_id' => $fund->id,
                'user_id' => $this->user->id,
                'nickname' => 'Investor Account',
            ]);
        $accountResponse->assertStatus(302);

        $userAccount = AccountExt::where('fund_id', $fund->id)
            ->where('user_id', $this->user->id)
            ->first();
        $this->assertNotNull($userAccount);

        // 4. Create transaction for user account
        $transResponse = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'account_id' => $userAccount->id,
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_PENDING,
                'value' => 5000.00,
                'timestamp' => now()->format('Y-m-d'),
            ]);
        $transResponse->assertStatus(302);

        // 5. Create additional portfolio
        $portfolioResponse = $this->actingAs($this->user)
            ->post(route('portfolios.store'), [
                'fund_id' => $fund->id,
                'source' => 'WORKFLOW_PORTFOLIO_2',
            ]);
        $portfolioResponse->assertStatus(302);

        // 6. Verify everything in index pages
        $fundIndexResponse = $this->actingAs($this->user)
            ->get(route('funds.index'));
        $fundIndexResponse->assertSee('Complete Workflow Fund');

        $accountIndexResponse = $this->actingAs($this->user)
            ->get(route('accounts.index'));
        $accountIndexResponse->assertSee('Investor Account');

        $transIndexResponse = $this->actingAs($this->user)
            ->get(route('transactions.index'));
        $transIndexResponse->assertSee('5000.00');

        $portfolioIndexResponse = $this->actingAs($this->user)
            ->get(route('portfolios.index'));
        $portfolioIndexResponse->assertSee('WORKFLOW_PORTFOLIO');
        $portfolioIndexResponse->assertSee('WORKFLOW_PORTFOLIO_2');

        // 7. Verify fund structure
        $this->assertEquals(2, AccountExt::where('fund_id', $fund->id)->count()); // Fund + User account
        $this->assertEquals(2, Portfolio::where('fund_id', $fund->id)->count()); // 2 portfolios
        $this->assertGreaterThanOrEqual(2, TransactionExt::whereIn('account_id',
            AccountExt::where('fund_id', $fund->id)->pluck('id'))->count()); // Initial + purchase
    }

    // ==================== Data Display Tests ====================

    public function test_fund_shows_correct_balance_after_creation()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Balance Display Fund',
                'portfolio_source' => 'BALANCE_DISPLAY',
                'create_initial_transaction' => true,
                'initial_shares' => 500,
                'initial_value' => 2500.00,
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Balance Display Fund')->first();
        $account = $fund->account();

        // Verify account balance
        $balance = $account->accountBalances()->first();
        $this->assertNotNull($balance);
        $this->assertEquals(2500.00, $balance->balance);
        $this->assertEquals(500, $balance->shares);
        $this->assertEquals(5.00, $balance->share_value);

        // Verify shows on fund page
        $response = $this->actingAs($this->user)
            ->get(route('funds.show', $fund->id));

        $response->assertSee('2,500.00'); // Formatted balance
    }

    public function test_portfolio_source_displays_correctly()
    {
        // Create fund via setup
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Source Display Fund',
                'portfolio_source' => 'MONARCH_IBKR_XXXX',
                'preview' => 0,
            ]);

        // Verify source displays on portfolio pages
        $portfolio = Portfolio::where('source', 'MONARCH_IBKR_XXXX')->first();

        $response = $this->actingAs($this->user)
            ->get(route('portfolios.show', $portfolio->id));

        $response->assertSee('MONARCH_IBKR_XXXX');
    }

    public function test_account_nickname_displays_correctly()
    {
        // Create fund via setup with custom nickname
        $this->actingAs($this->user)
            ->post(route('funds.storeWithSetup'), [
                'name' => 'Nickname Display Fund',
                'account_nickname' => 'Custom Fund Account Nickname',
                'portfolio_source' => 'NICKNAME_DISPLAY',
                'preview' => 0,
            ]);

        $fund = Fund::where('name', 'Nickname Display Fund')->first();
        $account = $fund->account();

        // Verify on account show page
        $response = $this->actingAs($this->user)
            ->get(route('accounts.show', $account->id));

        $response->assertSee('Custom Fund Account Nickname');
    }
}
