<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Models\FundExt;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Spatie\Permission\Models\Role;

class UserRoleController extends AppBaseController
{
    /**
     * Check if current user is system admin.
     */
    private function isSystemAdmin(): bool
    {
        $user = auth()->user();
        return $user && $user->isSystemAdmin();
    }

    /**
     * Display list of users with their roles.
     */
    public function index()
    {
        if (!$this->isSystemAdmin()) {
            Flash::error('Access denied. System admin only.');
            return redirect('/');
        }

        $users = User::with('roles')->orderBy('name')->get()->map(function ($user) {
            return [
                'user' => $user,
                'isSystemAdmin' => $user->isSystemAdmin(),
                'fundRoles' => $user->roles()
                    ->whereNotNull('roles.fund_id')
                    ->where('roles.fund_id', '!=', 0)
                    ->get()
                    ->groupBy('fund_id'),
            ];
        });

        $funds = FundExt::orderBy('name')->get();

        return view('admin.user-roles.index', compact('users', 'funds'));
    }

    /**
     * Show role management for a specific user.
     */
    public function show($id)
    {
        if (!$this->isSystemAdmin()) {
            Flash::error('Access denied. System admin only.');
            return redirect('/');
        }

        $user = User::with('roles')->findOrFail($id);
        $funds = FundExt::orderBy('name')->get();

        // Get current roles organized by fund
        $userRoles = [
            'systemAdmin' => $user->isSystemAdmin(),
            'fundRoles' => [],
        ];

        foreach ($funds as $fund) {
            $originalTeamId = getPermissionsTeamId();
            setPermissionsTeamId($fund->id);

            $userRoles['fundRoles'][$fund->id] = [
                'fund' => $fund,
                'roles' => $user->roles()
                    ->where('roles.fund_id', $fund->id)
                    ->pluck('name')
                    ->toArray(),
            ];

            setPermissionsTeamId($originalTeamId);
        }

        // Available role templates
        $roleTemplates = ['fund-admin', 'financial-manager', 'beneficiary'];

        return view('admin.user-roles.show', compact('user', 'userRoles', 'funds', 'roleTemplates'));
    }

    /**
     * Assign a role to a user.
     */
    public function assign(Request $request, $userId)
    {
        if (!$this->isSystemAdmin()) {
            Flash::error('Access denied. System admin only.');
            return redirect('/');
        }

        $request->validate([
            'role' => 'required|string|in:system-admin,fund-admin,financial-manager,beneficiary',
            'fund_id' => 'nullable|integer|exists:funds,id',
        ]);

        $user = User::findOrFail($userId);
        $roleName = $request->input('role');
        $fundId = $request->input('fund_id');

        if ($roleName === 'system-admin') {
            // Assign system-admin role with fund_id=0 for global access
            $role = Role::firstOrCreate([
                'name' => 'system-admin',
                'guard_name' => 'web',
                'fund_id' => 0,
            ]);

            $originalTeamId = getPermissionsTeamId();
            setPermissionsTeamId(0);

            if (!$user->hasRole('system-admin')) {
                $user->assignRole($role);
                Flash::success("System admin role assigned to {$user->name}.");
            } else {
                Flash::info("{$user->name} already has system admin role.");
            }

            setPermissionsTeamId($originalTeamId);
        } else {
            if (!$fundId) {
                Flash::error('Fund is required for fund-scoped roles.');
                return redirect()->back();
            }

            $fund = FundExt::findOrFail($fundId);

            // Create or get the fund-scoped role
            $role = RolesAndPermissionsSeeder::createFundRole($roleName, $fundId);

            // Check using our direct query method to avoid caching issues
            if (!$user->hasRoleInFund($roleName, $fundId)) {
                $originalTeamId = getPermissionsTeamId();
                setPermissionsTeamId($fundId);
                $user->assignRole($role);
                setPermissionsTeamId($originalTeamId);
                Flash::success("Role '{$roleName}' in fund '{$fund->name}' assigned to {$user->name}.");
            } else {
                Flash::info("{$user->name} already has role '{$roleName}' in fund '{$fund->name}'.");
            }
        }

        return redirect()->route('admin.user-roles.show', $userId);
    }

    /**
     * Revoke a role from a user.
     */
    public function revoke(Request $request, $userId)
    {
        if (!$this->isSystemAdmin()) {
            Flash::error('Access denied. System admin only.');
            return redirect('/');
        }

        $request->validate([
            'role' => 'required|string',
            'fund_id' => 'nullable|integer',
        ]);

        $user = User::findOrFail($userId);
        $roleName = $request->input('role');
        $fundId = $request->input('fund_id');

        // Prevent revoking your own system-admin role
        if ($roleName === 'system-admin' && $user->id === auth()->id()) {
            Flash::error('You cannot revoke your own system admin role.');
            return redirect()->back();
        }

        // System admin uses 0 for fund_id, other roles use the actual fund_id
        $teamId = ($roleName === 'system-admin') ? 0 : $fundId;

        // Check using direct query method to avoid caching issues
        $hasTheRole = ($roleName === 'system-admin')
            ? $user->isSystemAdmin()
            : $user->hasRoleInFund($roleName, $fundId);

        if ($hasTheRole) {
            // Use direct SQL to delete role assignment (handles User/UserExt model type issue)
            \DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->whereIn('model_type', [User::class, \App\Models\UserExt::class])
                ->where('fund_id', $teamId)
                ->whereExists(function ($query) use ($roleName) {
                    $query->select(\DB::raw(1))
                        ->from('roles')
                        ->whereColumn('roles.id', 'model_has_roles.role_id')
                        ->where('roles.name', $roleName);
                })
                ->delete();

            // Clear Spatie permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            if ($roleName === 'system-admin') {
                Flash::success("System admin role revoked from {$user->name}.");
            } else {
                $fund = FundExt::find($fundId);
                $fundName = $fund ? $fund->name : "Fund #{$fundId}";
                Flash::success("Role '{$roleName}' in '{$fundName}' revoked from {$user->name}.");
            }
        } else {
            Flash::info("{$user->name} does not have role '{$roleName}'.");
        }

        return redirect()->route('admin.user-roles.show', $userId);
    }
}
