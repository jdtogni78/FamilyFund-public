<?php

namespace App\Policies;

use App\Models\FundExt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundPolicy
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
     * Determine whether the user can view any funds.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('funds.view');
    }

    /**
     * Determine whether the user can view the fund.
     */
    public function view(User $user, FundExt $fund): bool
    {
        // Check if user has any role in this fund
        $accessibleFunds = $user->getAccessibleFundIds();
        $allAccessibleFundIds = array_merge(
            $accessibleFunds['full'],
            $accessibleFunds['readonly']
        );

        return in_array($fund->id, $allAccessibleFundIds);
    }

    /**
     * Determine whether the user can create funds.
     */
    public function create(User $user): bool
    {
        // Only system admins can create funds (handled in before())
        return false;
    }

    /**
     * Determine whether the user can update the fund.
     */
    public function update(User $user, FundExt $fund): bool
    {
        // Only fund admins can update funds
        return $user->hasRoleInFund('fund-admin', $fund->id);
    }

    /**
     * Determine whether the user can delete the fund.
     */
    public function delete(User $user, FundExt $fund): bool
    {
        // Only system admins can delete funds (handled in before())
        return false;
    }
}
