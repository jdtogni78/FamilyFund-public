<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Account permissions
            'accounts.view',
            'accounts.view-own',
            'accounts.create',
            'accounts.update',
            'accounts.delete',

            // Transaction permissions
            'transactions.view',
            'transactions.view-own',
            'transactions.create',
            'transactions.process',
            'transactions.delete',

            // Fund permissions
            'funds.view',
            'funds.update',

            // Portfolio permissions
            'portfolios.view',
            'portfolios.update',

            // Report permissions
            'reports.view',
            'reports.generate',

            // System admin permission
            'system.admin',

            // User management permissions
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign-roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create system-admin role (global - no fund_id)
        $systemAdmin = Role::firstOrCreate([
            'name' => 'system-admin',
            'guard_name' => 'web',
            'fund_id' => null,
        ]);
        $systemAdmin->syncPermissions(Permission::all());

        // Create fund-scoped roles (will be created per-fund when needed)
        // These are template definitions - actual roles are created with fund_id when assigning

        // Fund Admin - full access within a fund
        $fundAdminPermissions = [
            'accounts.view',
            'accounts.create',
            'accounts.update',
            'accounts.delete',
            'transactions.view',
            'transactions.create',
            'transactions.process',
            'transactions.delete',
            'funds.view',
            'funds.update',
            'portfolios.view',
            'portfolios.update',
            'reports.view',
            'reports.generate',
            'users.view',
            'users.assign-roles',
        ];

        // Financial Manager - transactions and deposits focus
        $financialManagerPermissions = [
            'accounts.view',
            'transactions.view',
            'transactions.create',
            'transactions.process',
            'funds.view',
            'portfolios.view',
            'reports.view',
            'reports.generate',
        ];

        // Beneficiary - view own account only
        $beneficiaryPermissions = [
            'accounts.view-own',
            'transactions.view-own',
            'funds.view',
            'reports.view',
        ];

        // Store role templates in a config-style structure for reference
        // These will be created dynamically when users are assigned to funds
        $this->command->info('Permissions seeded successfully.');
        $this->command->info('System-admin role created with all permissions.');
        $this->command->info('');
        $this->command->info('Fund-scoped roles will be created when assigning users to funds.');
        $this->command->info('Available role templates: fund-admin, financial-manager, beneficiary');
    }

    /**
     * Get the permissions for a given role template.
     * This can be used when creating fund-scoped roles.
     */
    public static function getPermissionsForRole(string $role): array
    {
        return match ($role) {
            'fund-admin' => [
                'accounts.view',
                'accounts.create',
                'accounts.update',
                'accounts.delete',
                'transactions.view',
                'transactions.create',
                'transactions.process',
                'transactions.delete',
                'funds.view',
                'funds.update',
                'portfolios.view',
                'portfolios.update',
                'reports.view',
                'reports.generate',
                'users.view',
                'users.assign-roles',
            ],
            'financial-manager' => [
                'accounts.view',
                'transactions.view',
                'transactions.create',
                'transactions.process',
                'funds.view',
                'portfolios.view',
                'reports.view',
                'reports.generate',
            ],
            'beneficiary' => [
                'accounts.view-own',
                'transactions.view-own',
                'funds.view',
                'reports.view',
            ],
            default => [],
        };
    }

    /**
     * Create or get a fund-scoped role.
     */
    public static function createFundRole(string $roleName, int $fundId): Role
    {
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
            'fund_id' => $fundId,
        ]);

        $permissions = self::getPermissionsForRole($roleName);
        $role->syncPermissions($permissions);

        return $role;
    }
}
