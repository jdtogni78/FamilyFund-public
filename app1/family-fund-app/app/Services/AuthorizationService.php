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
     * Scope a credit lines query to only return credit lines the user can access.
     */
    public function scopeCreditLinesQuery(Builder $query): Builder
    {
        if (!$this->user) {
            return $query->whereRaw('1 = 0'); // No access
        }

        if ($this->user->isSystemAdmin()) {
            return $query; // Full access
        }

        $accessibleFunds = $this->user->getAccessibleFundIds();
        $ownAccountIds = $this->user->getOwnAccountIds();

        // User can see credit lines for accounts in funds they have full access to
        // OR credit lines for their own accounts (as beneficiary)
        return $query->where(function ($q) use ($accessibleFunds, $ownAccountIds) {
            // Full access to funds - credit lines through accounts
            if (!empty($accessibleFunds['full'])) {
                $q->orWhereHas('account', function ($accountQuery) use ($accessibleFunds) {
                    $accountQuery->whereIn('fund_id', $accessibleFunds['full']);
                });
            }

            // Own accounts' credit lines
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
     * Scope a query whose model belongs to an account, returning only rows the
     * user can access. Generalizes scopeTransactionsQuery/scopeCreditLinesQuery
     * for any account-owned resource (account_reports, account_balances,
     * account_matching_rules, ...).
     *
     * Access = rows whose account is in a fully-accessible fund OR whose
     * account is one the user owns directly (beneficiary). Unlike the older
     * per-model scopes, this denies-by-default when the user has no access.
     *
     * @param string $accountFk foreign-key column on the model (e.g. account_id)
     * @param string $relation  the belongsTo-account relation name on the model
     */
    public function scopeByAccountRelation(Builder $query, string $accountFk = 'account_id', string $relation = 'account'): Builder
    {
        if (!$this->user) {
            return $query->whereRaw('1 = 0'); // No access
        }

        if ($this->user->isSystemAdmin()) {
            return $query; // Full access
        }

        $accessibleFunds = $this->user->getAccessibleFundIds();
        $ownAccountIds = $this->user->getOwnAccountIds();

        // Deny-by-default: a user with neither full-fund access nor own accounts
        // must not see any account-owned rows.
        if (empty($accessibleFunds['full']) && empty($ownAccountIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($accessibleFunds, $ownAccountIds, $accountFk, $relation) {
            if (!empty($accessibleFunds['full'])) {
                $q->orWhereHas($relation, function ($accountQuery) use ($accessibleFunds) {
                    $accountQuery->whereIn('fund_id', $accessibleFunds['full']);
                });
            }

            if (!empty($ownAccountIds)) {
                $q->orWhereIn($accountFk, $ownAccountIds);
            }
        });
    }

    /**
     * Scope a query whose model has a direct fund column, returning only rows
     * in funds the user can access (full or readonly). Denies by default.
     *
     * @param string $fundFk foreign-key column on the model (e.g. fund_id)
     */
    public function scopeByFundColumn(Builder $query, string $fundFk = 'fund_id'): Builder
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

        return $query->whereIn($fundFk, $allAccessibleFundIds);
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
