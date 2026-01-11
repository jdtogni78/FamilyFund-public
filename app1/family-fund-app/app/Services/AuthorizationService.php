<?php

namespace App\Services;

use App\Models\AccountExt;
use App\Models\FundExt;
use App\Models\TransactionExt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AuthorizationService
{
    protected ?User $user;

    public function __construct(?User $user = null)
    {
        $this->user = $user ?? Auth::user();
    }

    /**
     * Get a new instance for a specific user.
     */
    public static function for(User $user): self
    {
        return new self($user);
    }

    /**
     * Scope an accounts query to only return accounts the user can access.
     */
    public function scopeAccountsQuery(Builder $query): Builder
    {
        if (!$this->user) {
            return $query->whereRaw('1 = 0'); // No access
        }

        if ($this->user->isSystemAdmin()) {
            return $query; // Full access
        }

        $accessibleFunds = $this->user->getAccessibleFundIds();
        $ownAccountIds = $this->user->getOwnAccountIds();

        // User can see accounts in funds they have full access to
        // OR their own accounts (as beneficiary)
        return $query->where(function ($q) use ($accessibleFunds, $ownAccountIds) {
            // Full access to funds
            if (!empty($accessibleFunds['full'])) {
                $q->orWhereIn('fund_id', $accessibleFunds['full']);
            }

            // Own accounts (regardless of fund)
            if (!empty($ownAccountIds)) {
                $q->orWhereIn('id', $ownAccountIds);
            }
        });
    }

    /**
     * Scope a transactions query to only return transactions the user can access.
     */
    public function scopeTransactionsQuery(Builder $query): Builder
    {
        if (!$this->user) {
            return $query->whereRaw('1 = 0'); // No access
        }

        if ($this->user->isSystemAdmin()) {
            return $query; // Full access
        }

        $accessibleFunds = $this->user->getAccessibleFundIds();
        $ownAccountIds = $this->user->getOwnAccountIds();

        // User can see transactions for accounts in funds they have full access to
        // OR transactions for their own accounts (as beneficiary)
        return $query->where(function ($q) use ($accessibleFunds, $ownAccountIds) {
            // Full access to funds - transactions through accounts
            if (!empty($accessibleFunds['full'])) {
                $q->orWhereHas('account', function ($accountQuery) use ($accessibleFunds) {
                    $accountQuery->whereIn('fund_id', $accessibleFunds['full']);
                });
            }

            // Own accounts' transactions
            if (!empty($ownAccountIds)) {
                $q->orWhereIn('account_id', $ownAccountIds);
            }
        });
    }

    /**
     * Scope a funds query to only return funds the user can access.
     */
    public function scopeFundsQuery(Builder $query): Builder
    {
        if (!$this->user) {
            return $query->whereRaw('1 = 0'); // No access
        }

        if ($this->user->isSystemAdmin()) {
            return $query; // Full access
        }

        $accessibleFunds = $this->user->getAccessibleFundIds();
        $allAccessibleFundIds = array_merge(
            $accessibleFunds['full'],
            $accessibleFunds['readonly']
        );

        if (empty($allAccessibleFundIds)) {
            return $query->whereRaw('1 = 0'); // No access
        }

        return $query->whereIn('id', $allAccessibleFundIds);
    }

    /**
     * Get all fund IDs the user has access to.
     *
     * @return array{full: array<int>, readonly: array<int>}
     */
    public function getAccessibleFundIds(): array
    {
        if (!$this->user) {
            return ['full' => [], 'readonly' => []];
        }

        return $this->user->getAccessibleFundIds();
    }

    /**
     * Check if user can view a specific account.
     */
    public function canViewAccount(AccountExt $account): bool
    {
        if (!$this->user) {
            return false;
        }

        return $this->user->canAccessAccount($account);
    }

    /**
     * Check if user can modify a specific account.
     */
    public function canModifyAccount(AccountExt $account): bool
    {
        if (!$this->user) {
            return false;
        }

        if ($this->user->isSystemAdmin()) {
            return true;
        }

        // Beneficiaries cannot modify accounts
        // Only fund-admin or financial-manager can modify
        $fundId = $account->fund_id;
        return $this->user->hasRoleInFund('fund-admin', $fundId)
            || $this->user->hasRoleInFund('financial-manager', $fundId);
    }

    /**
     * Check if user can view a specific transaction.
     */
    public function canViewTransaction(TransactionExt $transaction): bool
    {
        if (!$this->user) {
            return false;
        }

        if ($this->user->isSystemAdmin()) {
            return true;
        }

        $account = $transaction->account;
        if (!$account) {
            return false;
        }

        return $this->canViewAccount($account);
    }

    /**
     * Check if user can view a specific fund.
     */
    public function canViewFund(FundExt $fund): bool
    {
        if (!$this->user) {
            return false;
        }

        if ($this->user->isSystemAdmin()) {
            return true;
        }

        $accessibleFunds = $this->user->getAccessibleFundIds();
        $allAccessibleFundIds = array_merge(
            $accessibleFunds['full'],
            $accessibleFunds['readonly']
        );

        return in_array($fund->id, $allAccessibleFundIds);
    }

    /**
     * Check if user can modify a specific fund.
     */
    public function canModifyFund(FundExt $fund): bool
    {
        if (!$this->user) {
            return false;
        }

        if ($this->user->isSystemAdmin()) {
            return true;
        }

        return $this->user->hasRoleInFund('fund-admin', $fund->id);
    }
}
