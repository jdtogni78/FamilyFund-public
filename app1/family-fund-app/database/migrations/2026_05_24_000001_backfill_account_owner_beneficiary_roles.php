<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Backfill: every account owner gets the fund-scoped `beneficiary` role on their
 * account's fund.
 *
 * Background: accounts carry a `user_id`, but a user could only ever *view* their
 * own account/dashboard if they also held a role on that account's fund — owning
 * the account is not enough (AccountPolicy::viewAny and the dashboard both gate on
 * "has a role in some fund"). Prod data accumulated account owners with no role at
 * all, so beneficiaries hit a 403 / "your account isn't linked to a fund yet" page
 * on their own account. This grants the minimal `beneficiary` role to close that
 * gap.
 *
 * SAFETY / IDEMPOTENCY:
 *  - Only ADDS a role; never removes or changes an existing one.
 *  - Skips any user who already holds *any* role on that fund (never downgrades a
 *    fund-admin / financial-manager).
 *  - Skips system admins (global access already).
 *  - Re-runnable: firstOrCreate on the role + a hasRole() guard before assigning.
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // createFundRole() calls syncPermissions(), which throws if a permission
        // name doesn't exist yet. Ensure the beneficiary permission set is present
        // (idempotent) so this migration is self-contained on a fresh prod DB.
        foreach (RolesAndPermissionsSeeder::getPermissionsForRole('beneficiary') as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $pairs = DB::table('accounts')
            ->whereNotNull('user_id')
            ->whereNotNull('fund_id')
            ->select('user_id', 'fund_id')
            ->distinct()
            ->get();

        $originalTeam = getPermissionsTeamId();

        $granted = 0;
        foreach ($pairs as $pair) {
            $user = User::find($pair->user_id);
            if (! $user) {
                continue;
            }

            // Global admins already have access; don't touch them.
            if ($user->isSystemAdmin()) {
                continue;
            }

            // Don't downgrade an existing fund role (fund-admin / financial-manager
            // / an already-assigned beneficiary).
            $hasRoleOnFund = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('roles.fund_id', $pair->fund_id)
                ->exists();
            if ($hasRoleOnFund) {
                continue;
            }

            $role = RolesAndPermissionsSeeder::createFundRole('beneficiary', (int) $pair->fund_id);

            setPermissionsTeamId((int) $pair->fund_id);
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
                $granted++;
            }
        }

        setPermissionsTeamId($originalTeam);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        echo "  backfill: granted beneficiary to {$granted} account owner(s).\n";
    }

    public function down(): void
    {
        // No-op: backfilled grants are indistinguishable from pre-existing
        // beneficiary roles, so reversing could revoke legitimate access.
    }
};
