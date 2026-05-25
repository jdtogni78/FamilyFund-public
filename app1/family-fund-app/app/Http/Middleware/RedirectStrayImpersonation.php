<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dev-only convenience for ACL testing.
 *
 * The `?as=` impersonation switch is honored ONLY by the /dev-login route.
 * Appending `?as=...` to any other URL silently does nothing — you keep
 * browsing as your previous user — which is a confusing footgun (it looks like
 * a cross-tenant leak when it's really "you're still the admin").
 *
 * This turns the footgun into a feature: in local/dev/testing, a stray `?as=`
 * on a normal GET request is redirected through /dev-login/<same-path>,
 * preserving the rest of the query string, so the impersonation always takes
 * effect. No-op in production (the /dev-login route doesn't exist there, so we
 * never redirect into a 404).
 */
class RedirectStrayImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            app()->environment('local', 'dev', 'testing')
            && $request->isMethod('GET')
            && $request->filled('as')
            && ! $request->is('dev-login', 'dev-login/*')
        ) {
            $path = trim($request->path(), '/');     // "" for root, "accounts/9" otherwise
            $query = $request->getQueryString();     // e.g. "as=account:7&fund_id=2"

            return redirect('/dev-login/' . $path . ($query ? '?' . $query : ''));
        }

        return $next($request);
    }
}
