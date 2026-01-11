<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\FundExt;
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

        // Clear Spatie permission cache before each test
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create a simple fund and account without DataFactory dependencies
        $this->fund = Fund::factory()->create();
        $this->beneficiary = User::factory()->create();

        // Create an account linked to the beneficiary
        $this->account = AccountExt::create([
            'fund_id' => $this->fund->id,
            'user_id' => $this->beneficiary->id,
            'code' => 'TEST001',
            'nickname' => 'Test Account',
            'type' => 'individual',
        ]);

        $fundId = $this->fund->id;

        // Create users with different roles
        $this->systemAdmin = User::factory()->create();
        $this->makeSystemAdmin($this->systemAdmin);

        $this->fundAdmin = User::factory()->create();
        $this->assignFundRole($this->fundAdmin, 'fund-admin', $fundId);

        $this->financialManager = User::factory()->create();
        $this->assignFundRole($this->financialManager, 'financial-manager', $fundId);

        $this->assignFundRole($this->beneficiary, 'beneficiary', $fundId);

        $this->unassignedUser = User::factory()->create();
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

    // ==================== Helper Methods ====================

    private function makeSystemAdmin(User $user): void
    {
        // Clear permission cache before creating/assigning role
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // System admin role uses fund_id=0 for global access
        $role = Role::firstOrCreate([
            'name' => 'system-admin',
            'guard_name' => 'web',
            'fund_id' => 0,
        ]);

        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $user->assignRole($role);
        setPermissionsTeamId($originalTeamId);

        // Force reload of roles relationship
        $user->load('roles');
    }

    private function assignFundRole(User $user, string $roleName, int $fundId): void
    {
        // Clear permission cache before creating/assigning role
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = RolesAndPermissionsSeeder::createFundRole($roleName, $fundId);

        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId($fundId);
        $user->assignRole($role);
        setPermissionsTeamId($originalTeamId);

        // Force reload of roles relationship
        $user->load('roles');
    }
}
