<?php

namespace App\Policies;

use App\Models\AccountExt;
use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // System admins bypass all checks
        if ($user->isSystemAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any accounts.
     */
    public function viewAny(User $user): bool
    {
        // Users with any fund role can view accounts (their access is filtered by query scoping)
        $fundAccess = $user->getAccessibleFundIds();
        return !empty($fundAccess['full']) || !empty($fundAccess['readonly']);
    }

    /**
     * Determine whether the user can view the account.
     */
    public function view(User $user, AccountExt $account): bool
    {
        // Fund admin or financial manager can view any account in their fund
        if ($user->hasRoleInFund('fund-admin', $account->fund_id)
            || $user->hasRoleInFund('financial-manager', $account->fund_id)) {
            return true;
        }

        // Beneficiary can view their own account
        if ($user->id === $account->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create accounts.
     */
    public function create(User $user): bool
    {
        // Users with full access to any fund can create accounts
        $fundAccess = $user->getAccessibleFundIds();
        return !empty($fundAccess['full']);
    }

    /**
     * Determine whether the user can update the account.
     */
    public function update(User $user, AccountExt $account): bool
    {
        // Only fund admins and financial managers can update accounts
        return $user->hasRoleInFund('fund-admin', $account->fund_id)
            || $user->hasRoleInFund('financial-manager', $account->fund_id);
    }

    /**
     * Determine whether the user can delete the account.
     */
    public function delete(User $user, AccountExt $account): bool
    {
        // Only fund admins can delete accounts
        return $user->hasRoleInFund('fund-admin', $account->fund_id);
    }
}
