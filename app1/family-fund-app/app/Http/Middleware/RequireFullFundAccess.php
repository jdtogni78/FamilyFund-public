<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireFullFundAccess
{
    /**
     * Require a system-admin role or at least one full-access fund role.
     *
     * This protects generated/admin-style CRUD surfaces that are not tied to
     * a single account policy. Beneficiaries can still read their own account
     * and fund pages through the explicit account/fund policies.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSystemAdmin()) {
            return $next($request);
        }

        $accessibleFunds = $user->getAccessibleFundIds();
        if (! empty($accessibleFunds['full'])) {
            return $next($request);
        }

        abort(403);
    }
}
