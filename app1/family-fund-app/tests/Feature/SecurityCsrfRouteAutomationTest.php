<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityCsrfRouteAutomationTest extends TestCase
{
    private const MUTATION_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Intentionally public web mutation endpoints.
     *
     * These routes still run through the web middleware group, which includes
     * Laravel's CSRF middleware. New public web mutations should be added here
     * only after explicit review.
     */
    private const PUBLIC_WEB_MUTATION_ALLOWLIST = [
        '/',
        'livewire/update',
        'livewire/upload-file',
        'two-factor-challenge',
    ];

    public function test_web_mutation_routes_stay_in_web_middleware_group(): void
    {
        $violations = [];

        foreach (Route::getRoutes() as $route) {
            if (!$this->hasMutationMethod($route->methods())) {
                continue;
            }

            $uri = $route->uri();
            if (Str::startsWith($uri, 'api/')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            if (in_array('web', $middleware, true)) {
                continue;
            }

            $violations[] = $this->describeRoute($route);
        }

        $this->assertSame(
            [],
            $violations,
            "Web mutation routes must stay in the web middleware group so CSRF/session protections apply.\n" . implode("\n", $violations)
        );
    }

    public function test_business_web_mutation_routes_require_auth(): void
    {
        $violations = [];

        foreach (Route::getRoutes() as $route) {
            if (!$this->hasMutationMethod($route->methods())) {
                continue;
            }

            $uri = $route->uri();
            if (Str::startsWith($uri, 'api/')) {
                continue;
            }

            if ($this->uriMatchesAny($uri, self::PUBLIC_WEB_MUTATION_ALLOWLIST)) {
                continue;
            }

            if ($this->hasAuthMiddleware($route->gatherMiddleware())) {
                continue;
            }

            $violations[] = $this->describeRoute($route);
        }

        $this->assertSame(
            [],
            $violations,
            "Business web mutation routes must require authentication.\n" . implode("\n", $violations)
        );
    }

    public function test_no_project_level_csrf_exemptions_are_configured(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertStringNotContainsString('validateCsrfTokens', $bootstrap, 'Project-level CSRF exemptions must be explicitly reviewed.');
        $this->assertStringNotContainsString('VerifyCsrfToken::except', $bootstrap, 'Project-level CSRF exemptions must be explicitly reviewed.');
    }

    /**
     * @param array<int,string> $methods
     */
    private function hasMutationMethod(array $methods): bool
    {
        return count(array_intersect($methods, self::MUTATION_METHODS)) > 0;
    }

    /**
     * @param array<int,string> $middleware
     */
    private function hasAuthMiddleware(array $middleware): bool
    {
        foreach ($middleware as $name) {
            if (
                $name === 'auth'
                || Str::startsWith($name, 'auth:')
                || Str::startsWith($name, 'Illuminate\Auth\Middleware\Authenticate')
            ) {
                return true;
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
