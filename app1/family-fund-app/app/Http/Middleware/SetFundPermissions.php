<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetFundPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $fundId = $this->determineFundId($request);

        if ($fundId) {
            setPermissionsTeamId($fundId);
        }

        return $next($request);
    }

    /**
     * Determine the fund_id from the request.
     */
    protected function determineFundId(Request $request): ?int
    {
        // 1. Check route parameter (e.g., /funds/{fund}/...)
        if ($fundId = $request->route('fund')) {
            return is_object($fundId) ? $fundId->id : (int) $fundId;
        }

        // 2. Check route parameter for fund_id
        if ($fundId = $request->route('fund_id')) {
            return (int) $fundId;
        }

        // 3. Check request body/query for fund_id
        if ($fundId = $request->input('fund_id')) {
            return (int) $fundId;
        }

        // 4. Check if we're accessing an account and get fund from there
        if ($account = $request->route('account')) {
            if (is_object($account)) {
                return $account->fund_id;
            }
            // If it's an ID, we'd need to load the account - skip for now
        }

        // 5. Check for accounts with account_id parameter
        if ($accountId = $request->route('account_id')) {
            $account = \App\Models\AccountExt::find($accountId);
            if ($account) {
                return $account->fund_id;
            }
        }

        return null;
    }
}
