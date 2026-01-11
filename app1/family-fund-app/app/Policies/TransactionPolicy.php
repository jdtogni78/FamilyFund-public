<?php

namespace App\Policies;

use App\Models\TransactionExt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
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
     * Determine whether the user can view any transactions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'transactions.view',
            'transactions.view-own',
        ]);
    }

    /**
     * Determine whether the user can view the transaction.
     */
    public function view(User $user, TransactionExt $transaction): bool
    {
        $account = $transaction->account;

        if (!$account) {
            return false;
        }

        // Fund admin or financial manager can view any transaction in their fund
        if ($user->hasRoleInFund('fund-admin', $account->fund_id)
            || $user->hasRoleInFund('financial-manager', $account->fund_id)) {
            return true;
        }

        // Beneficiary can view transactions on their own account
        if ($user->id === $account->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create transactions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('transactions.create');
    }

    /**
     * Determine whether the user can update the transaction.
     */
    public function update(User $user, TransactionExt $transaction): bool
    {
        $account = $transaction->account;

        if (!$account) {
            return false;
        }

        // Only fund admins and financial managers can update transactions
        return $user->hasRoleInFund('fund-admin', $account->fund_id)
            || $user->hasRoleInFund('financial-manager', $account->fund_id);
    }

    /**
     * Determine whether the user can delete the transaction.
     */
    public function delete(User $user, TransactionExt $transaction): bool
    {
        $account = $transaction->account;

        if (!$account) {
            return false;
        }

        // Only fund admins can delete transactions
        return $user->hasRoleInFund('fund-admin', $account->fund_id);
    }

    /**
     * Determine whether the user can process the transaction.
     */
    public function process(User $user, TransactionExt $transaction): bool
    {
        $account = $transaction->account;

        if (!$account) {
            return false;
        }

        // Fund admins and financial managers can process transactions
        return $user->hasRoleInFund('fund-admin', $account->fund_id)
            || $user->hasRoleInFund('financial-manager', $account->fund_id);
    }
}
