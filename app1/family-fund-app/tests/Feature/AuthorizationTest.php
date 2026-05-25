<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\FundExt;
use App\Models\PortfolioAsset;
use App\Models\PortfolioExt;
use App\Models\TradePortfolioExt;
use App\Models\TradePortfolioItemExt;
use App\Models\TransactionExt;
use App\Models\User;
use App\Policies\AccountPolicy;
use App\Policies\FundPolicy;
use App\Policies\TransactionPolicy;
use App\Services\AuthorizationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\TestFixtures;
use Tests\TestCase;

/**
 * Tests for Authorization Service and Policies.
 */
class AuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private Fund $fund;
    private AccountExt $account;
    private User $systemAdmin;
    private User $fundAdmin;
    private User $financialManager;
    private User $beneficiary;
    private User $unassignedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions (required for permission checks)
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->fund = Fund::factory()->create();

        $users = TestFixtures::aclUsers($this->fund);
        $this->systemAdmin       = $users['systemAdmin'];
        $this->fundAdmin         = $users['fundAdmin'];
        $this->financialManager  = $users['financialManager'];
        $this->beneficiary       = $users['beneficiary'];
        $this->unassignedUser    = $users['unassigned'];
        $this->account           = $users['beneficiaryAccount'];
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Authorization Service Tests ====================

    public function test_authorization_service_scopes_accounts_for_system_admin()
    {
        $service = new AuthorizationService($this->systemAdmin);
        $query = AccountExt::query();

        $service->scopeAccountsQuery($query);

        // System admin should see all accounts
        $this->assertGreaterThan(0, $query->count());
    }

    public function test_authorization_service_scopes_accounts_for_fund_admin()
    {
        $service = new AuthorizationService($this->fundAdmin);
        $query = AccountExt::query();

        $service->scopeAccountsQuery($query);

        // Fund admin should see accounts in their fund
        $accounts = $query->get();
        foreach ($accounts as $account) {
            $this->assertEquals($this->fund->id, $account->fund_id);
        }
    }

    public function test_authorization_service_scopes_accounts_for_beneficiary()
    {
        $service = new AuthorizationService($this->beneficiary);
        $query = AccountExt::query();

        $service->scopeAccountsQuery($query);

        // Beneficiary should only see their own accounts
        $accounts = $query->get();
        foreach ($accounts as $account) {
            $this->assertEquals($this->beneficiary->id, $account->user_id);
        }
    }

    public function test_legacy_scopes_deny_user_without_fund_access_or_own_accounts()
    {
        // Regression for #74: the legacy scopes built their predicate as
        // ->where(fn ($q) => ...orWhere...). For a user with no full-fund access
        // AND no own accounts, neither inner clause was added, the nested closure
        // compiled to no WHERE, and the query leaked every row. Create real rows
        // owned by the beneficiary, then assert the unassigned user sees none.
        $transaction = \Database\Factories\TransactionFactory::new()
            ->create(['account_id' => $this->account->id]);
        \Database\Factories\AccountCreditLineFactory::new()
            ->create(['account_id' => $this->account->id]);

        $unassigned = new AuthorizationService($this->unassignedUser);
        $this->assertSame(0, $unassigned->scopeAccountsQuery(AccountExt::query())->count(),
            'unassigned user must see no accounts');
        $this->assertSame(0, $unassigned->scopeTransactionsQuery(TransactionExt::query())->count(),
            'unassigned user must see no transactions');
        $this->assertSame(0, $unassigned->scopeCreditLinesQuery(\App\Models\AccountCreditLineExt::query())->count(),
            'unassigned user must see no credit lines');

        // Sanity: the owner still sees their own rows, so the scope filters
        // rather than simply returning nothing.
        $owner = new AuthorizationService($this->beneficiary);
        $this->assertGreaterThan(0, $owner->scopeAccountsQuery(AccountExt::query())->count());
        $this->assertGreaterThan(0, $owner->scopeTransactionsQuery(TransactionExt::query())->count());
        $this->assertGreaterThan(0, $owner->scopeCreditLinesQuery(\App\Models\AccountCreditLineExt::query())->count());
    }

    public function test_authorization_service_can_view_account()
    {
        $account = $this->account;

        // System admin can view any account
        $systemAdminService = new AuthorizationService($this->systemAdmin);
        $this->assertTrue($systemAdminService->canViewAccount($account));

        // Fund admin can view accounts in their fund
        $fundAdminService = new AuthorizationService($this->fundAdmin);
        $this->assertTrue($fundAdminService->canViewAccount($account));

        // Beneficiary can view their own account
        $beneficiaryService = new AuthorizationService($this->beneficiary);
        $this->assertTrue($beneficiaryService->canViewAccount($account));

        // Unassigned user cannot view the account
        $unassignedService = new AuthorizationService($this->unassignedUser);
        $this->assertFalse($unassignedService->canViewAccount($account));
    }

    public function test_authorization_service_can_modify_account()
    {
        $account = $this->account;

        // System admin can modify any account
        $systemAdminService = new AuthorizationService($this->systemAdmin);
        $this->assertTrue($systemAdminService->canModifyAccount($account));

        // Fund admin can modify accounts in their fund
        $fundAdminService = new AuthorizationService($this->fundAdmin);
        $this->assertTrue($fundAdminService->canModifyAccount($account));

        // Beneficiary cannot modify accounts
        $beneficiaryService = new AuthorizationService($this->beneficiary);
        $this->assertFalse($beneficiaryService->canModifyAccount($account));
    }

    // ==================== Account Policy Tests ====================

    public function test_account_policy_view_any()
    {
        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('viewAny', AccountExt::class));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('viewAny', AccountExt::class));
        $this->assertTrue(Gate::forUser($this->financialManager)->allows('viewAny', AccountExt::class));
        $this->assertTrue(Gate::forUser($this->beneficiary)->allows('viewAny', AccountExt::class));
    }

    public function test_account_policy_view()
    {
        $account = $this->account;

        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('view', $account));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('view', $account));
        $this->assertTrue(Gate::forUser($this->beneficiary)->allows('view', $account));
    }

    public function test_account_policy_create()
    {
        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('create', AccountExt::class));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('create', AccountExt::class));
        $this->assertFalse(Gate::forUser($this->beneficiary)->allows('create', AccountExt::class));
    }

    public function test_account_policy_update()
    {
        $account = $this->account;

        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('update', $account));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('update', $account));
        $this->assertFalse(Gate::forUser($this->beneficiary)->allows('update', $account));
    }

    public function test_account_policy_delete()
    {
        $account = $this->account;

        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('delete', $account));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('delete', $account));
        $this->assertFalse(Gate::forUser($this->beneficiary)->allows('delete', $account));
    }

    // ==================== Fund Policy Tests ====================

    public function test_fund_policy_view()
    {
        $fund = FundExt::find($this->fund->id);

        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('view', $fund));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('view', $fund));
        $this->assertTrue(Gate::forUser($this->financialManager)->allows('view', $fund));
        $this->assertTrue(Gate::forUser($this->beneficiary)->allows('view', $fund));
    }

    public function test_fund_policy_update()
    {
        $fund = FundExt::find($this->fund->id);

        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('update', $fund));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('update', $fund));
        $this->assertFalse(Gate::forUser($this->financialManager)->allows('update', $fund));
        $this->assertFalse(Gate::forUser($this->beneficiary)->allows('update', $fund));
    }

    // ==================== Transaction Policy Tests ====================

    public function test_transaction_policy_view_any()
    {
        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('viewAny', TransactionExt::class));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('viewAny', TransactionExt::class));
        $this->assertTrue(Gate::forUser($this->financialManager)->allows('viewAny', TransactionExt::class));
        $this->assertTrue(Gate::forUser($this->beneficiary)->allows('viewAny', TransactionExt::class));
    }

    public function test_transaction_policy_create()
    {
        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('create', TransactionExt::class));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('create', TransactionExt::class));
        $this->assertTrue(Gate::forUser($this->financialManager)->allows('create', TransactionExt::class));
        $this->assertFalse(Gate::forUser($this->beneficiary)->allows('create', TransactionExt::class));
    }

    // ==================== User canAccessAccount Tests ====================

    public function test_user_can_access_own_account()
    {
        $account = $this->account;

        $this->assertTrue($this->beneficiary->canAccessAccount($account));
    }

    public function test_user_cannot_access_other_users_account()
    {
        $account = $this->account;

        $this->assertFalse($this->unassignedUser->canAccessAccount($account));
    }

    public function test_system_admin_can_access_any_account()
    {
        $account = $this->account;

        $this->assertTrue($this->systemAdmin->canAccessAccount($account));
    }

    public function test_fund_admin_can_access_accounts_in_fund()
    {
        $account = $this->account;

        $this->assertTrue($this->fundAdmin->canAccessAccount($account));
    }

    // ==================== Additional Authorization Service Tests ====================

    public function test_authorization_service_for_static_method()
    {
        $service = AuthorizationService::for($this->systemAdmin);
        $this->assertTrue($service->canViewAccount($this->account));
    }

    public function test_authorization_service_scopes_accounts_for_null_user()
    {
        $service = new AuthorizationService(null);
        $query = AccountExt::query();

        $service->scopeAccountsQuery($query);

        // Null user should see no accounts
        $this->assertEquals(0, $query->count());
    }

    public function test_authorization_service_scopes_transactions_for_system_admin()
    {
        // Create a transaction for testing
        $transaction = $this->createTestTransaction();

        $service = new AuthorizationService($this->systemAdmin);
        $query = TransactionExt::query();

        $service->scopeTransactionsQuery($query);

        // System admin should see all transactions
        $this->assertGreaterThan(0, $query->count());
    }

    public function test_authorization_service_scopes_transactions_for_fund_admin()
    {
        $transaction = $this->createTestTransaction();

        $service = new AuthorizationService($this->fundAdmin);
        $query = TransactionExt::query();

        $service->scopeTransactionsQuery($query);

        // Fund admin should see transactions in their fund
        $transactions = $query->get();
        foreach ($transactions as $tx) {
            $account = AccountExt::find($tx->account_id);
            $this->assertEquals($this->fund->id, $account->fund_id);
        }
    }

    public function test_authorization_service_scopes_transactions_for_beneficiary()
    {
        $transaction = $this->createTestTransaction();

        $service = new AuthorizationService($this->beneficiary);
        $query = TransactionExt::query();

        $service->scopeTransactionsQuery($query);

        // Beneficiary should only see their own account transactions
        $transactions = $query->get();
        foreach ($transactions as $tx) {
            $this->assertEquals($this->account->id, $tx->account_id);
        }
    }

    public function test_authorization_service_scopes_transactions_for_null_user()
    {
        $service = new AuthorizationService(null);
        $query = TransactionExt::query();

        $service->scopeTransactionsQuery($query);

        // Null user should see no transactions
        $this->assertEquals(0, $query->count());
    }

    public function test_authorization_service_scopes_funds_for_system_admin()
    {
        $service = new AuthorizationService($this->systemAdmin);
        $query = FundExt::query();

        $service->scopeFundsQuery($query);

        // System admin should see all funds
        $this->assertGreaterThan(0, $query->count());
    }

    public function test_authorization_service_scopes_funds_for_fund_admin()
    {
        $service = new AuthorizationService($this->fundAdmin);
        $query = FundExt::query();

        $service->scopeFundsQuery($query);

        // Fund admin should see only accessible funds
        $funds = $query->get();
        $this->assertTrue($funds->pluck('id')->contains($this->fund->id));
    }

    public function test_authorization_service_scopes_funds_for_null_user()
    {
        $service = new AuthorizationService(null);
        $query = FundExt::query();

        $service->scopeFundsQuery($query);

        // Null user should see no funds
        $this->assertEquals(0, $query->count());
    }

    public function test_authorization_service_scopes_funds_for_unassigned_user()
    {
        $service = new AuthorizationService($this->unassignedUser);
        $query = FundExt::query();

        $service->scopeFundsQuery($query);

        // Unassigned user should see no funds
        $this->assertEquals(0, $query->count());
    }

    public function test_authorization_service_get_accessible_fund_ids_for_null_user()
    {
        $service = new AuthorizationService(null);
        $fundIds = $service->getAccessibleFundIds();

        $this->assertEmpty($fundIds['full']);
        $this->assertEmpty($fundIds['readonly']);
    }

    public function test_authorization_service_get_accessible_fund_ids_for_fund_admin()
    {
        // Non-null user → the service delegates to the user's own lookup.
        $service = new AuthorizationService($this->fundAdmin);
        $fundIds = $service->getAccessibleFundIds();

        $this->assertContains($this->fund->id, $fundIds['full']);
    }

    public function test_authorization_service_can_view_account_for_null_user()
    {
        $service = new AuthorizationService(null);
        $this->assertFalse($service->canViewAccount($this->account));
    }

    public function test_authorization_service_can_modify_account_for_null_user()
    {
        $service = new AuthorizationService(null);
        $this->assertFalse($service->canModifyAccount($this->account));
    }

    public function test_authorization_service_can_view_transaction()
    {
        $transaction = $this->createTestTransaction();

        // System admin can view any transaction
        $systemAdminService = new AuthorizationService($this->systemAdmin);
        $this->assertTrue($systemAdminService->canViewTransaction($transaction));

        // Fund admin can view transactions in their fund
        $fundAdminService = new AuthorizationService($this->fundAdmin);
        $this->assertTrue($fundAdminService->canViewTransaction($transaction));

        // Beneficiary can view their own account transactions
        $beneficiaryService = new AuthorizationService($this->beneficiary);
        $this->assertTrue($beneficiaryService->canViewTransaction($transaction));

        // Null user cannot view transaction
        $nullService = new AuthorizationService(null);
        $this->assertFalse($nullService->canViewTransaction($transaction));
    }

    public function test_authorization_service_can_view_fund()
    {
        $fund = FundExt::find($this->fund->id);

        // System admin can view any fund
        $systemAdminService = new AuthorizationService($this->systemAdmin);
        $this->assertTrue($systemAdminService->canViewFund($fund));

        // Fund admin can view their fund
        $fundAdminService = new AuthorizationService($this->fundAdmin);
        $this->assertTrue($fundAdminService->canViewFund($fund));

        // Beneficiary can view their fund
        $beneficiaryService = new AuthorizationService($this->beneficiary);
        $this->assertTrue($beneficiaryService->canViewFund($fund));

        // Null user cannot view fund
        $nullService = new AuthorizationService(null);
        $this->assertFalse($nullService->canViewFund($fund));
    }

    public function test_authorization_service_can_modify_fund()
    {
        $fund = FundExt::find($this->fund->id);

        // System admin can modify any fund
        $systemAdminService = new AuthorizationService($this->systemAdmin);
        $this->assertTrue($systemAdminService->canModifyFund($fund));

        // Fund admin can modify their fund
        $fundAdminService = new AuthorizationService($this->fundAdmin);
        $this->assertTrue($fundAdminService->canModifyFund($fund));

        // Financial manager cannot modify fund
        $financialManagerService = new AuthorizationService($this->financialManager);
        $this->assertFalse($financialManagerService->canModifyFund($fund));

        // Null user cannot modify fund
        $nullService = new AuthorizationService(null);
        $this->assertFalse($nullService->canModifyFund($fund));
    }

    // ==================== Additional Transaction Policy Tests ====================

    public function test_transaction_policy_view()
    {
        $transaction = $this->createTestTransaction();

        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('view', $transaction));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('view', $transaction));
        $this->assertTrue(Gate::forUser($this->beneficiary)->allows('view', $transaction));
    }

    public function test_transaction_policy_update()
    {
        $transaction = $this->createTestTransaction();

        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('update', $transaction));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('update', $transaction));
        $this->assertFalse(Gate::forUser($this->beneficiary)->allows('update', $transaction));
    }

    public function test_transaction_policy_delete()
    {
        $transaction = $this->createTestTransaction();

        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('delete', $transaction));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('delete', $transaction));
        $this->assertFalse(Gate::forUser($this->beneficiary)->allows('delete', $transaction));
    }

    public function test_transaction_policy_process()
    {
        $transaction = $this->createTestTransaction();

        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('process', $transaction));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('process', $transaction));
        $this->assertTrue(Gate::forUser($this->financialManager)->allows('process', $transaction));
        $this->assertFalse(Gate::forUser($this->beneficiary)->allows('process', $transaction));
    }

    // ==================== Additional Fund Policy Tests ====================

    public function test_fund_policy_view_any()
    {
        // Use Gate facade to properly invoke before() hook
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('viewAny', FundExt::class));
        $this->assertTrue(Gate::forUser($this->fundAdmin)->allows('viewAny', FundExt::class));
        $this->assertTrue(Gate::forUser($this->financialManager)->allows('viewAny', FundExt::class));
        $this->assertTrue(Gate::forUser($this->beneficiary)->allows('viewAny', FundExt::class));
    }

    public function test_fund_policy_create()
    {
        // Use Gate facade to properly invoke before() hook
        // Only system admin can create funds
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('create', FundExt::class));
        $this->assertFalse(Gate::forUser($this->fundAdmin)->allows('create', FundExt::class));
        $this->assertFalse(Gate::forUser($this->beneficiary)->allows('create', FundExt::class));
    }

    public function test_fund_policy_delete()
    {
        $fund = FundExt::find($this->fund->id);

        // Use Gate facade to properly invoke before() hook
        // Only system admin can delete funds
        $this->assertTrue(Gate::forUser($this->systemAdmin)->allows('delete', $fund));
        $this->assertFalse(Gate::forUser($this->fundAdmin)->allows('delete', $fund));
        $this->assertFalse(Gate::forUser($this->beneficiary)->allows('delete', $fund));
    }

    // ==================== Credit Line Scoping Tests ====================

    public function test_authorization_service_scopes_credit_lines_for_system_admin()
    {
        $this->createTestCreditLine();

        $service = new AuthorizationService($this->systemAdmin);
        $query = \App\Models\AccountCreditLineExt::query();

        $service->scopeCreditLinesQuery($query);

        // System admin should see all credit lines.
        $this->assertGreaterThan(0, $query->count());
    }

    public function test_authorization_service_scopes_credit_lines_for_fund_admin()
    {
        $this->createTestCreditLine();

        $service = new AuthorizationService($this->fundAdmin);
        $query = \App\Models\AccountCreditLineExt::query();

        $service->scopeCreditLinesQuery($query);

        // Fund admin should only see credit lines for accounts in their fund.
        $creditLines = $query->get();
        $this->assertGreaterThan(0, $creditLines->count());
        foreach ($creditLines as $cl) {
            $account = AccountExt::find($cl->account_id);
            $this->assertEquals($this->fund->id, $account->fund_id);
        }
    }

    public function test_authorization_service_scopes_credit_lines_for_beneficiary()
    {
        $this->createTestCreditLine();

        $service = new AuthorizationService($this->beneficiary);
        $query = \App\Models\AccountCreditLineExt::query();

        $service->scopeCreditLinesQuery($query);

        // Beneficiary should only see credit lines on their own account.
        $creditLines = $query->get();
        foreach ($creditLines as $cl) {
            $this->assertEquals($this->account->id, $cl->account_id);
        }
    }

    public function test_authorization_service_scopes_credit_lines_for_null_user()
    {
        $this->createTestCreditLine();

        $service = new AuthorizationService(null);
        $query = \App\Models\AccountCreditLineExt::query();

        $service->scopeCreditLinesQuery($query);

        // Null user should see no credit lines.
        $this->assertEquals(0, $query->count());
    }

    // ==================== Helper Methods ====================

    private function createTestCreditLine(): \App\Models\AccountCreditLineExt
    {
        // AccountCreditLineExt has no convention-named factory; the existing
        // factory class is keyed to the base name but builds the Ext model.
        return \Database\Factories\AccountCreditLineFactory::new()->create([
            'account_id' => $this->account->id,
        ]);
    }

    // ============ #85: portfolio subtree + management-surface scoping ============

    public function test_scope_portfolios_query_includes_pivot_and_excludes_other_fund(): void
    {
        $otherFund = Fund::factory()->create();

        $mine  = \Database\Factories\PortfolioFactory::new()->create(['fund_id' => $this->fund->id]);
        $other = \Database\Factories\PortfolioFactory::new()->create(['fund_id' => $otherFund->id]);

        // A portfolio linked to my fund ONLY through the fund_portfolio pivot
        // (legacy fund_id null) must still be visible — this is why plain
        // scopeByFundColumn is insufficient for portfolios.
        $pivot = \Database\Factories\PortfolioFactory::new()->create(['fund_id' => null]);
        $pivot->funds()->attach($this->fund->id);

        $ids = (new AuthorizationService($this->fundAdmin))
            ->scopePortfoliosQuery(PortfolioExt::query())->pluck('id');

        $this->assertTrue($ids->contains($mine->id), 'own-fund portfolio visible');
        $this->assertTrue($ids->contains($pivot->id), 'pivot-linked portfolio visible');
        $this->assertFalse($ids->contains($other->id), 'other-fund portfolio hidden');

        // System admin sees every portfolio.
        $adminIds = (new AuthorizationService($this->systemAdmin))
            ->scopePortfoliosQuery(PortfolioExt::query())->pluck('id');
        $this->assertTrue($adminIds->contains($other->id), 'system admin sees all funds');
    }

    public function test_scope_by_portfolio_relation_scopes_children(): void
    {
        $otherFund = Fund::factory()->create();
        $myPortfolio    = \Database\Factories\PortfolioFactory::new()->create(['fund_id' => $this->fund->id]);
        $otherPortfolio = \Database\Factories\PortfolioFactory::new()->create(['fund_id' => $otherFund->id]);

        $myPA    = \Database\Factories\PortfolioAssetFactory::new()->create(['portfolio_id' => $myPortfolio->id]);
        $otherPA = \Database\Factories\PortfolioAssetFactory::new()->create(['portfolio_id' => $otherPortfolio->id]);

        $myTP    = \Database\Factories\TradePortfolioFactory::new()->create(['portfolio_id' => $myPortfolio->id]);
        $otherTP = \Database\Factories\TradePortfolioFactory::new()->create(['portfolio_id' => $otherPortfolio->id]);

        $myItem    = \Database\Factories\TradePortfolioItemFactory::new()->create(['trade_portfolio_id' => $myTP->id]);
        $otherItem = \Database\Factories\TradePortfolioItemFactory::new()->create(['trade_portfolio_id' => $otherTP->id]);

        $service = new AuthorizationService($this->fundAdmin);

        // Direct portfolio_id children use the column-based scope (avoids the
        // overridden TradePortfolioExt::portfolio() accessor).
        $paIds = $service->scopeByPortfolioColumn(PortfolioAsset::query())->pluck('id');
        $this->assertTrue($paIds->contains($myPA->id), 'own portfolio asset visible');
        $this->assertFalse($paIds->contains($otherPA->id), 'other-fund portfolio asset hidden');

        $tpIds = $service->scopeByPortfolioColumn(TradePortfolioExt::query())->pluck('id');
        $this->assertTrue($tpIds->contains($myTP->id), 'own trade portfolio visible');
        $this->assertFalse($tpIds->contains($otherTP->id), 'other-fund trade portfolio hidden');

        // Two-hop child: trade_portfolio_items -> tradePortfolio -> portfolio -> fund
        // (clean relations, so the relation-based scope is safe here).
        $itemIds = $service->scopeByPortfolioRelation(TradePortfolioItemExt::query(), 'tradePortfolio.portfolio')->pluck('id');
        $this->assertTrue($itemIds->contains($myItem->id), 'own trade portfolio item visible');
        $this->assertFalse($itemIds->contains($otherItem->id), 'other-fund trade portfolio item hidden');
    }

    public function test_portfolio_scopes_deny_user_without_fund_access(): void
    {
        $fund = Fund::factory()->create();
        $portfolio = \Database\Factories\PortfolioFactory::new()->create(['fund_id' => $fund->id]);
        \Database\Factories\PortfolioAssetFactory::new()->create(['portfolio_id' => $portfolio->id]);

        $unassigned = new AuthorizationService($this->unassignedUser);

        $this->assertSame(0, $unassigned->scopePortfoliosQuery(PortfolioExt::query())->count(),
            'unassigned user must see no portfolios');
        $this->assertSame(0, $unassigned->scopeByPortfolioColumn(PortfolioAsset::query())->count(),
            'unassigned user must see no portfolio assets');
    }

    public function test_can_access_management_surface(): void
    {
        $this->assertTrue((new AuthorizationService($this->systemAdmin))->canAccessManagementSurface(),
            'system admin can reach management surface');
        $this->assertTrue((new AuthorizationService($this->fundAdmin))->canAccessManagementSurface(),
            'fund-admin (full access) can reach management surface');
        $this->assertTrue((new AuthorizationService($this->financialManager))->canAccessManagementSurface(),
            'financial-manager (full access) can reach management surface');
        $this->assertFalse((new AuthorizationService($this->beneficiary))->canAccessManagementSurface(),
            'beneficiary (readonly only) cannot reach management surface');
        $this->assertFalse((new AuthorizationService($this->unassignedUser))->canAccessManagementSurface(),
            'unassigned user cannot reach management surface');
        $this->assertFalse((new AuthorizationService(null))->canAccessManagementSurface(),
            'guest cannot reach management surface');
    }

    private function createTestTransaction(): TransactionExt
    {
        return TransactionExt::create([
            'account_id' => $this->account->id,
            'type' => 'PUR',  // Purchase type (3-char code)
            'value' => 1000,
            'timestamp' => now(),
            'status' => 'S',  // Submitted status (1-char code)
        ]);
    }
}
