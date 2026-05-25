<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityRouteAutomationTest extends TestCase
{
    private const AUTH_MIDDLEWARE_PREFIXES = [
        'auth',
        'auth:',
        'auth.basic',
        'auth.session',
        'Illuminate\Auth\Middleware\Authenticate',
    ];

    /**
     * Public web routes that are intentionally unauthenticated.
     */
    private const PUBLIC_WEB_ROUTE_ALLOWLIST = [
        '/',
        '_dusk/*',
        'dev-login',
        'dev-login/*',
        'forgot-password',
        'livewire/*',
        'livewire-dusk/*',
        'login',
        'register',
        'reset-password/*',
        'sanctum/csrf-cookie',
        'storage/*',
        'two-factor-challenge',
        'up',
    ];

    /**
     * Temporary baseline for current unauthenticated API mutation routes.
     *
     * These are not approvals. They make route drift visible now, while the
     * existing API auth model is reviewed and tightened route by route.
     */
    private const TEMPORARY_UNAUTHENTICATED_API_MUTATION_ALLOWLIST = [];

    /**
     * Temporary baseline for current unauthenticated API read routes.
     *
     * These routes include known security findings from the 2026-05 pentest (doc removed; see git history; tickets #49/#13).
     * New public API reads must not be added silently.
     *
     * Empty by decision: #51 (item C) / ED-0012 classified every `api/` endpoint
     * — there are no intentionally-public API endpoints, so this list is
     * deny-by-default. See test_audited_api_endpoints_are_authenticated_not_public.
     */
    private const TEMPORARY_UNAUTHENTICATED_API_READ_ALLOWLIST = [];

    /**
     * Endpoints the 2026-05 route audit (#14 item C) singled out as the open
     * "is this intentionally public?" questions. Decision (#51 / ED-0012): none
     * are public — they are pinned authenticated here by name.
     */
    private const AUDITED_API_ENDPOINTS = [
        'api/funds/{id}/overview-data',
        'api/exchange_holidays/{exchange}/{year}',
        'api/exchange_holidays/status',
        'api/exchange_holidays/sync',
    ];

    /**
     * Legacy GET endpoints whose names imply side effects.
     *
     * Empty: the resend/send-all/announce endpoints were migrated to POST with
     * CSRF (#50). This list now exists only to catch any new side-effect-like
     * GET action being added quietly — keep it empty.
     */
    private const TEMPORARY_SIDE_EFFECT_GET_ALLOWLIST = [];

    public function test_api_mutation_routes_require_auth_or_are_in_temporary_baseline(): void
    {
        $violations = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!Str::startsWith($uri, 'api/')) {
                continue;
            }

            if (!$this->hasMutationMethod($route->methods())) {
                continue;
            }

            if ($this->hasAuthMiddleware($route->gatherMiddleware())) {
                continue;
            }

            if ($this->uriMatchesAny($uri, self::TEMPORARY_UNAUTHENTICATED_API_MUTATION_ALLOWLIST)) {
                continue;
            }

            $violations[] = $this->describeRoute($route);
        }

        $this->assertSame(
            [],
            $violations,
            "New unauthenticated API mutation routes must not be added.\n" . implode("\n", $violations)
        );
    }

    public function test_api_read_routes_require_auth_or_are_in_temporary_baseline(): void
    {
        $violations = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!Str::startsWith($uri, 'api/')) {
                continue;
            }

            if (!$this->hasReadMethod($route->methods())) {
                continue;
            }

            if ($this->hasAuthMiddleware($route->gatherMiddleware())) {
                continue;
            }

            if ($this->uriMatchesAny($uri, self::TEMPORARY_UNAUTHENTICATED_API_READ_ALLOWLIST)) {
                continue;
            }

            $violations[] = $this->describeRoute($route);
        }

        $this->assertSame(
            [],
            $violations,
            "New unauthenticated API read routes must not be added.\n" . implode("\n", $violations)
        );
    }

    public function test_web_business_routes_require_auth_or_are_public_baseline(): void
    {
        $violations = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (Str::startsWith($uri, 'api/')) {
                continue;
            }

            if ($this->hasAuthMiddleware($route->gatherMiddleware())) {
                continue;
            }

            if ($this->uriMatchesAny($uri, self::PUBLIC_WEB_ROUTE_ALLOWLIST)) {
                continue;
            }

            $violations[] = $this->describeRoute($route);
        }

        $this->assertSame(
            [],
            $violations,
            "New unauthenticated web routes must be intentionally added to the public baseline.\n" . implode("\n", $violations)
        );
    }

    public function test_side_effect_named_get_routes_are_limited_to_temporary_baseline(): void
    {
        $violations = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (Str::startsWith($uri, 'api/')) {
                continue;
            }

            if (!in_array('GET', $route->methods(), true)) {
                continue;
            }

            if (!$this->looksLikeSideEffectRoute($uri, (string) $route->getName())) {
                continue;
            }

            if (in_array($uri, self::TEMPORARY_SIDE_EFFECT_GET_ALLOWLIST, true)) {
                continue;
            }

            $violations[] = $this->describeRoute($route);
        }

        $this->assertSame(
            [],
            $violations,
            "New side-effect-like GET routes must use POST/PUT/PATCH/DELETE instead.\n" . implode("\n", $violations)
        );
    }

    /**
     * #51 (item C) / ED-0012: the audited `api/`-prefixed endpoints are
     * classified as NOT intentionally public, so pin them authenticated by name.
     *
     * The generic guards above already reject any unauthenticated `api/` route,
     * but they go silent if a route is simply removed — so they would not catch a
     * later delete-and-readd of one of these as a public endpoint. This asserts
     * the audited endpoints both still exist and still carry auth middleware.
     */
    public function test_audited_api_endpoints_are_authenticated_not_public(): void
    {
        $routesByUri = [];
        foreach (Route::getRoutes() as $route) {
            $routesByUri[$route->uri()] = $route;
        }

        foreach (self::AUDITED_API_ENDPOINTS as $uri) {
            $this->assertArrayHasKey(
                $uri,
                $routesByUri,
                "Audited endpoint {$uri} is missing. If it was intentionally removed, update ED-0012; it must never reappear unauthenticated."
            );

            $this->assertTrue(
                $this->hasAuthMiddleware($routesByUri[$uri]->gatherMiddleware()),
                "Audited endpoint {$uri} must stay authenticated (ED-0012: no intentionally-public API endpoints)."
            );
        }
    }

    public function test_dev_login_route_is_environment_gated_in_source(): void
    {
        $source = file_get_contents(base_path('routes/web.php'));

        // Match the guard regardless of the exact non-prod env list: the
        // dev-login block is gated by app()->environment('local', 'dev', 'testing')
        // ('testing' lets the ACL feature tests exercise impersonation). The
        // prefix below still asserts the local/dev guard exists and excludes prod.
        $guardPosition = strpos($source, "if (app()->environment('local', 'dev'");
        $routePosition = strpos($source, "Route::get('/dev-login");

        $this->assertNotFalse($guardPosition, 'routes/web.php must guard dev-login with a local/dev environment check.');
        $this->assertNotFalse($routePosition, 'routes/web.php should contain the dev-login route.');
        $this->assertLessThan($routePosition, $guardPosition, 'The local/dev guard must appear before the dev-login route.');
    }

    public function test_api_clear_route_is_environment_gated_in_source(): void
    {
        $source = file_get_contents(base_path('routes/api.php'));

        $guardPosition = strpos($source, "if (app()->environment('local', 'dev'))");
        $routePosition = strpos($source, "Route::get('/clear");

        $this->assertNotFalse($guardPosition, 'routes/api.php must guard /api/clear with a local/dev environment check.');
        $this->assertNotFalse($routePosition, 'routes/api.php should contain the local/dev cache-clear route.');
        $this->assertLessThan($routePosition, $guardPosition, 'The local/dev guard must appear before the /api/clear route.');
    }

    /**
     * @param array<int,string> $methods
     */
    private function hasMutationMethod(array $methods): bool
    {
        return count(array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE'])) > 0;
    }

    /**
     * @param array<int,string> $methods
     */
    private function hasReadMethod(array $methods): bool
    {
        return count(array_intersect($methods, ['GET', 'HEAD'])) > 0;
    }

    /**
     * @param array<int,string> $middleware
     */
    private function hasAuthMiddleware(array $middleware): bool
    {
        foreach ($middleware as $name) {
            foreach (self::AUTH_MIDDLEWARE_PREFIXES as $prefix) {
                if ($name === $prefix || Str::startsWith($name, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<int,string> $patterns
     */
    private function uriMatchesAny(string $uri, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeSideEffectRoute(string $uri, string $name): bool
    {
        $subject = $uri . ' ' . $name;

        return (bool) preg_match(
            '/\b(resend|send-all|send|process|run|force-run|flush|retry|sync|store|update|delete|destroy|announce)\b/i',
            $subject
        );
    }

    private function describeRoute(\Illuminate\Routing\Route $route): string
    {
        return sprintf(
            '%s %s %s [%s]',
            implode('|', $route->methods()),
            $route->uri(),
            $route->getName() ?: '(unnamed)',
            implode(',', $route->gatherMiddleware())
        );
    }
}
