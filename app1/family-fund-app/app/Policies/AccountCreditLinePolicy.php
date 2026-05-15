<?php

namespace App\Policies;

use App\Models\AccountCreditLineExt;
use App\Models\AccountExt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountCreditLinePolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * System admins bypass all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any credit lines for the given account.
     * Same access rules as viewing the account itself.
     */
    public function viewAny(User $user, AccountExt $account): bool
    {
        // Fund admin or financial manager can view credit lines for any account in their fund
        if ($user->hasRoleInFund('fund-admin', $account->fund_id)
            || $user->hasRoleInFund('financial-manager', $account->fund_id)) {
            return true;
        }

        // Beneficiary can view credit lines for their own account
        if ($user->id === $account->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the credit line.
     * Checks access to the related account.
     */
    public function view(User $user, AccountCreditLineExt $creditLine): bool
    {
        $account = $creditLine->account;
        $fundId = $account->fund_id;

        // Fund admin or financial manager can view any credit line in their fund
        if ($user->hasRoleInFund('fund-admin', $fundId)
            || $user->hasRoleInFund('financial-manager', $fundId)) {
            return true;
        }

        // Beneficiary can view credit lines for their own account
        if ($user->id === $account->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create a credit line for the given account.
     * Requires fund-admin or financial-manager role for the account's fund.
     */
    public function create(User $user, AccountExt $account): bool
    {
        return $user->hasRoleInFund('fund-admin', $account->fund_id)
            || $user->hasRoleInFund('financial-manager', $account->fund_id);
    }

    /**
     * Determine whether the user can update the credit line.
     * Requires fund-admin or financial-manager role for the account's fund.
     */
    public function update(User $user, AccountCreditLineExt $creditLine): bool
    {
        $fundId = $creditLine->account->fund_id;

        return $user->hasRoleInFund('fund-admin', $fundId)
            || $user->hasRoleInFund('financial-manager', $fundId);
    }

    /**
     * Determine whether the user can delete (cancel) the credit line.
     * Only fund-admin can cancel credit lines.
     */
    public function delete(User $user, AccountCreditLineExt $creditLine): bool
    {
        $fundId = $creditLine->account->fund_id;

        return $user->hasRoleInFund('fund-admin', $fundId);
    }

    /**
     * Determine whether the user can process the credit line (repay/readjust).
     * Requires fund-admin or financial-manager role for the account's fund.
     */
    public function process(User $user, AccountCreditLineExt $creditLine): bool
    {
        $fundId = $creditLine->account->fund_id;

        return $user->hasRoleInFund('fund-admin', $fundId)
            || $user->hasRoleInFund('financial-manager', $fundId);
    }
}
