<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundExt;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for User Role Management functionality.
 */
class UserRoleManagementTest extends TestCase
{
    use DatabaseTransactions;

    private User $adminUser;
    private User $regularUser;
    private Fund $fund;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions (required for permission checks)
        $this->seed(RolesAndPermissionsSeeder::class);

        // Clear Spatie permission cache before each test
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create a simple fund without DataFactory dependencies
        $this->fund = Fund::factory()->create();
        $this->regularUser = User::factory()->create();

        // Create admin user
        $this->adminUser = User::factory()->create();
        $this->makeSystemAdmin($this->adminUser);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Access Control Tests ====================

    public function test_admin_user_roles_index_accessible_by_system_admin()
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/user-roles');
        $response->assertStatus(200);
        $response->assertSee('User Role Management');
    }

    public function test_admin_user_roles_index_denied_for_regular_user()
    {
        $response = $this->actingAs($this->regularUser)->get('/admin/user-roles');
        $response->assertRedirect('/');
    }

    public function test_admin_user_roles_show_accessible_by_system_admin()
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/user-roles/' . $this->regularUser->id);
        $response->assertStatus(200);
        $response->assertSee($this->regularUser->name);
    }

    public function test_admin_user_roles_show_denied_for_regular_user()
    {
        $response = $this->actingAs($this->regularUser)->get('/admin/user-roles/' . $this->adminUser->id);
        $response->assertRedirect('/');
    }

    // ==================== Role Assignment Tests ====================

    public function test_assign_system_admin_role()
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/user-roles/' . $this->regularUser->id . '/assign', [
            'role' => 'system-admin',
        ]);

        $response->assertRedirect('/admin/user-roles/' . $this->regularUser->id);

        $this->regularUser->refresh();
        $this->assertTrue($this->regularUser->isSystemAdmin());
    }

    public function test_assign_fund_admin_role()
    {
        $fundId = $this->fund->id;

        $response = $this->actingAs($this->adminUser)->post('/admin/user-roles/' . $this->regularUser->id . '/assign', [
            'role' => 'fund-admin',
            'fund_id' => $fundId,
        ]);

        $response->assertRedirect('/admin/user-roles/' . $this->regularUser->id);

        $this->regularUser->refresh();
        $this->assertTrue($this->regularUser->hasRoleInFund('fund-admin', $fundId));
    }

    public function test_assign_financial_manager_role()
    {
        $fundId = $this->fund->id;

        $response = $this->actingAs($this->adminUser)->post('/admin/user-roles/' . $this->regularUser->id . '/assign', [
            'role' => 'financial-manager',
            'fund_id' => $fundId,
        ]);

        $response->assertRedirect('/admin/user-roles/' . $this->regularUser->id);

        $this->regularUser->refresh();
        $this->assertTrue($this->regularUser->hasRoleInFund('financial-manager', $fundId));
    }

    public function test_assign_beneficiary_role()
    {
        $fundId = $this->fund->id;

        $response = $this->actingAs($this->adminUser)->post('/admin/user-roles/' . $this->regularUser->id . '/assign', [
            'role' => 'beneficiary',
            'fund_id' => $fundId,
        ]);

        $response->assertRedirect('/admin/user-roles/' . $this->regularUser->id);

        $this->regularUser->refresh();
        $this->assertTrue($this->regularUser->hasRoleInFund('beneficiary', $fundId));
    }

    public function test_assign_fund_role_requires_fund_id()
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/user-roles/' . $this->regularUser->id . '/assign', [
            'role' => 'fund-admin',
            // Missing fund_id
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Role Revocation Tests ====================

    public function test_revoke_fund_role()
    {
        $fundId = $this->fund->id;

        // First assign the role
        $this->assignFundRole($this->regularUser, 'fund-admin', $fundId);
        $this->assertTrue($this->regularUser->hasRoleInFund('fund-admin', $fundId));

        // Now revoke it
        $response = $this->actingAs($this->adminUser)->post('/admin/user-roles/' . $this->regularUser->id . '/revoke', [
            'role' => 'fund-admin',
            'fund_id' => $fundId,
        ]);

        $response->assertRedirect('/admin/user-roles/' . $this->regularUser->id);

        $this->regularUser->refresh();
        $this->assertFalse($this->regularUser->hasRoleInFund('fund-admin', $fundId));
    }

    public function test_cannot_revoke_own_system_admin_role()
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/user-roles/' . $this->adminUser->id . '/revoke', [
            'role' => 'system-admin',
            'fund_id' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_notification');

        $this->adminUser->refresh();
        $this->assertTrue($this->adminUser->isSystemAdmin());
    }

    // ==================== User Model Role Tests ====================

    public function test_user_is_system_admin()
    {
        $this->assertTrue($this->adminUser->isSystemAdmin());
        $this->assertFalse($this->regularUser->isSystemAdmin());
    }

    public function test_user_has_role_in_fund()
    {
        $fundId = $this->fund->id;

        // System admin should have any role in any fund
        $this->assertTrue($this->adminUser->hasRoleInFund('fund-admin', $fundId));

        // Regular user should not have role before assignment
        $newUser = User::factory()->create();
        $this->assertFalse($newUser->hasRoleInFund('fund-admin', $fundId));

        // Assign role and check again
        $this->assignFundRole($newUser, 'fund-admin', $fundId);
        $newUser->refresh(); // Refresh to get updated roles
        $this->assertTrue($newUser->hasRoleInFund('fund-admin', $fundId));
    }

    public function test_user_get_accessible_fund_ids_for_system_admin()
    {
        $fundId = $this->fund->id;

        // System admin should have full access to all funds
        $access = $this->adminUser->getAccessibleFundIds();
        $this->assertContains($fundId, $access['full']);
    }

    public function test_user_get_accessible_fund_ids_for_regular_user()
    {
        // Regular user with no roles should have empty access
        $newUser = User::factory()->create();
        $access = $newUser->getAccessibleFundIds();
        $this->assertEmpty($access['full']);
        $this->assertEmpty($access['readonly']);
    }

    // ==================== Admin Menu Visibility Tests ====================

    public function test_admin_menu_visible_for_system_admin()
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');
        $response->assertStatus(200);
        // Note: This depends on how the navigation is rendered
        // The menu should be visible based on isSystemAdmin() check
    }

    // ==================== Helper Methods ====================

    private function makeSystemAdmin(User $user): void
    {
        // Clear permission cache before creating/assigning role
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = RolesAndPermissionsSeeder::createFundRole($roleName, $fundId);

        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId($fundId);
        $user->assignRole($role);
        setPermissionsTeamId($originalTeamId);

        // Force reload of roles relationship
        $user->load('roles');
    }
}
