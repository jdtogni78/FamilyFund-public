<?php

namespace App\Http\Controllers\Traits;

use App\Models\AccountExt;
use App\Models\FundExt;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Object-level / tenant authorization helpers for the generated API resource
 * controllers (#13 / #49 item A).
 *
 * All `/api/*` routes sit behind `auth:sanctum`, but the generated resource
 * controllers otherwise return repository data with no tenant scoping, letting
 * an authenticated beneficiary read cross-tenant rows (IDOR). These helpers add
 * the object-level layer, mirroring the pattern already used by
 * AccountAPIController: a scoped index query plus an `abort(403)` guard on
 * show/update/destroy once the row (and its owning account/fund) is resolved.
 *
 * Convention (matches AccountAPIController): a missing row returns 404 via the
 * controller's own `sendError(...)`; an existing-but-forbidden row aborts 403.
 * System admins bypass every check (AuthorizationService short-circuits on
 * isSystemAdmin()).
 */
trait AuthorizesApiAccess
{
    protected function currentApiUser(): ?User
    {
        return request()->user();
    }

    protected function apiAuthz(): AuthorizationService
    {
        return new AuthorizationService($this->currentApiUser());
    }

    /**
     * Abort 403 unless the caller is a system admin. Used to lock down
     * generated endpoints that have no legitimate non-admin API consumer
     * (e.g. raw PII resources served through the web UI instead).
     */
    protected function requireSystemAdmin(): void
    {
        abort_unless((bool) $this->currentApiUser()?->isSystemAdmin(), 403);
    }

    /**
     * Gate a *write* to global shared/reference data behind system-admin (#82),
     * subject to the `familyfund.enforce_admin_writes` flag. When the flag is
     * off this is a no-op (auth:sanctum still applies). Call from the custom
     * bulk-write methods (e.g. asset_prices_bulk_update); for generated resource
     * controllers use adminWriteMiddleware() on store/update/destroy instead.
     */
    protected function requireAdminWrite(): void
    {
        if (config('familyfund.enforce_admin_writes', true)) {
            $this->requireSystemAdmin();
        }
    }

    /**
     * Controller middleware (for a ctor `$this->middleware(...)->only([...])`)
     * that enforces system-admin on reference-data write actions, honoring the
     * `familyfund.enforce_admin_writes` flag. Reads are never affected.
     */
    protected function adminWriteMiddleware(): \Closure
    {
        return function ($request, $next) {
            if (config('familyfund.enforce_admin_writes', true)) {
                abort_unless((bool) $request->user()?->isSystemAdmin(), 403);
            }

            return $next($request);
        };
    }

    /**
     * Abort 403 unless the caller can view (or, with $modify, modify) the
     * given account. A null account (missing / orphaned) is denied.
     */
    protected function requireAccountAccess(?AccountExt $account, bool $modify = false): void
    {
        if ($this->currentApiUser()?->isSystemAdmin()) {
            return;
        }

        $authz = $this->apiAuthz();
        $ok = $account !== null
            && ($modify ? $authz->canModifyAccount($account) : $authz->canViewAccount($account));

        abort_unless($ok, 403);
    }

    /**
     * Abort 403 unless the caller has full (fund-admin / financial-manager)
     * access to at least one fund. Mirrors the `create` ability on the
     * Account/Transaction policies for resource-store endpoints whose target
     * fund is not directly addressable from the request.
     */
    protected function requireFullAccessToAnyFund(): void
    {
        $user = $this->currentApiUser();
        if ($user?->isSystemAdmin()) {
            return;
        }

        abort_unless(!empty($user?->getAccessibleFundIds()['full'] ?? []), 403);
    }

    /**
     * Abort 403 unless the caller can view (or, with $modify, modify) the
     * given fund. A null fund is denied.
     */
    protected function requireFundAccess(?FundExt $fund, bool $modify = false): void
    {
        if ($this->currentApiUser()?->isSystemAdmin()) {
            return;
        }

        $authz = $this->apiAuthz();
        $ok = $fund !== null
            && ($modify ? $authz->canModifyFund($fund) : $authz->canViewFund($fund));

        abort_unless($ok, 403);
    }

    /**
     * Resolve the owning account (as AccountExt, the type the authz helpers
     * expect) for a child resource, by foreign-key id. Returns null when the
     * id is empty or the account no longer exists.
     */
    protected function resolveAccount($accountId): ?AccountExt
    {
        return $accountId ? AccountExt::find($accountId) : null;
    }

    /**
     * Resolve the owning fund (as FundExt) by foreign-key id.
     */
    protected function resolveFund($fundId): ?FundExt
    {
        return $fundId ? FundExt::find($fundId) : null;
    }

    // --- Portfolio subtree ---------------------------------------------------
    //
    // A portfolio belongs to its fund(s) via the legacy `fund_id` column AND the
    // `fund_portfolio` pivot, so both must be considered. Portfolios and their
    // children (portfolio_assets, portfolio_balances, trade_portfolios, ...) are
    // fund-management data: any role in the owning fund may read them; only
    // full-access roles (fund-admin / financial-manager) may modify them.

    /**
     * All fund ids a portfolio belongs to (legacy column + pivot).
     *
     * @return array<int>
     */
    protected function portfolioFundIds(Portfolio $portfolio): array
    {
        $ids = [];
        if ($portfolio->fund_id) {
            $ids[] = (int) $portfolio->fund_id;
        }
        foreach ($portfolio->funds as $fund) {
            $ids[] = (int) $fund->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Scope a portfolios query to those in funds the caller can access.
     */
    protected function scopePortfolioQuery(Builder $query): Builder
    {
        $user = $this->currentApiUser();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isSystemAdmin()) {
            return $query;
        }

        $access = $user->getAccessibleFundIds();
        $allIds = array_merge($access['full'], $access['readonly']);
        if (empty($allIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($allIds) {
            $q->whereIn('fund_id', $allIds)
                ->orWhereHas('funds', function ($fq) use ($allIds) {
                    $fq->whereIn('funds.id', $allIds);
                });
        });
    }

    /**
     * Whether the caller can view (or modify) the given portfolio. A null
     * portfolio is denied.
     */
    protected function canAccessPortfolio(?Portfolio $portfolio, bool $modify = false): bool
    {
        $user = $this->currentApiUser();
        if ($user?->isSystemAdmin()) {
            return true;
        }

        if ($portfolio === null) {
            return false;
        }

        $access = $user ? $user->getAccessibleFundIds() : ['full' => [], 'readonly' => []];
        $allowed = $modify ? $access['full'] : array_merge($access['full'], $access['readonly']);

        return (bool) array_intersect($this->portfolioFundIds($portfolio), $allowed);
    }

    /**
     * Abort 403 unless the caller can view (or modify) the given portfolio.
     * A null portfolio is denied.
     */
    protected function requirePortfolioAccess(?Portfolio $portfolio, bool $modify = false): void
    {
        abort_unless($this->canAccessPortfolio($portfolio, $modify), 403);
    }

    /**
     * Resolve a Portfolio by id (for guarding portfolio-child resources).
     */
    protected function resolvePortfolio($portfolioId): ?Portfolio
    {
        return $portfolioId ? Portfolio::find($portfolioId) : null;
    }
}
