<?php

namespace Tests\Feature;

use App\Http\Middleware\SetFundPermissions;
use App\Models\AccountExt;
use App\Models\Fund;
use App\Models\FundExt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as IlluminateRoute;
use Tests\TestCase;

/**
 * Unit tests for the SetFundPermissions middleware (Phase 4 RBAC coverage).
 *
 * The middleware runs on every web request and seeds Spatie's team-scoped
 * permission context from whatever fund the request is about. Each test below
 * exercises one branch of {@see SetFundPermissions::determineFundId()} by
 * resolving a request whose route/input exposes a different fund handle, then
 * asserting the resulting permissions team id.
 */
class SetFundPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    /** A team id no factory-created fund will collide with. */
    private const SENTINEL_TEAM_ID = 987654;

    private SetFundPermissions $middleware;
    private Fund $fund;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SetFundPermissions();
        $this->fund = Fund::factory()->create();
    }

    protected function tearDown(): void
    {
        // Don't leak the team id into sibling tests.
        setPermissionsTeamId(null);
        parent::tearDown();
    }

    // ==================== determineFundId branches ====================

    public function test_resolves_fund_from_route_fund_object()
    {
        $fund = FundExt::find($this->fund->id);
        $request = $this->requestWithRouteParams(['fund' => $fund]);

        $this->assertResolvesTeam($request, $this->fund->id);
    }

    public function test_resolves_fund_from_route_fund_id_string()
    {
        // A route-model-binding miss leaves the raw id in the route param.
        $request = $this->requestWithRouteParams(['fund' => (string) $this->fund->id]);

        $this->assertResolvesTeam($request, $this->fund->id);
    }

    public function test_resolves_fund_from_route_fund_id_parameter()
    {
        $request = $this->requestWithRouteParams(['fund_id' => $this->fund->id]);

        $this->assertResolvesTeam($request, $this->fund->id);
    }

    public function test_resolves_fund_from_request_input()
    {
        // No route params at all — falls through to the request body/query.
        $request = Request::create('/x?fund_id=' . $this->fund->id, 'GET');

        $this->assertResolvesTeam($request, $this->fund->id);
    }

    public function test_resolves_fund_from_route_account_object()
    {
        $account = $this->makeAccount();
        $request = $this->requestWithRouteParams(['account' => $account]);

        $this->assertResolvesTeam($request, $this->fund->id);
    }

    public function test_resolves_fund_from_route_account_id_lookup()
    {
        $account = $this->makeAccount();
        $request = $this->requestWithRouteParams(['account_id' => $account->id]);

        $this->assertResolvesTeam($request, $this->fund->id);
    }

    // ==================== Non-resolving / fall-through branches ====================

    public function test_does_not_set_team_when_no_fund_handle_present()
    {
        $request = $this->requestWithRouteParams([]);

        $this->assertResolvesNothing($request);
    }

    public function test_ignores_non_object_account_route_param()
    {
        // route('account') present but as a bare id (no route-model binding):
        // the middleware can't resolve it and falls through to "no fund".
        $request = $this->requestWithRouteParams(['account' => (string) $this->makeAccount()->id]);

        $this->assertResolvesNothing($request);
    }

    public function test_ignores_unknown_account_id()
    {
        $request = $this->requestWithRouteParams(['account_id' => 999999]);

        $this->assertResolvesNothing($request);
    }

    // ==================== Helpers ====================

    private function makeAccount(): AccountExt
    {
        return AccountExt::create([
            'fund_id' => $this->fund->id,
            'code' => 'SFP' . mt_rand(1000, 999999), // <= 15 chars (column limit)
            'nickname' => 'SetFundPermissions test account',
            'type' => 'individual',
        ]);
    }

    /**
     * Build a request whose resolved route exposes the given parameters,
     * mirroring how Laravel populates {@see Request::route()} after matching.
     */
    private function requestWithRouteParams(array $params, string $url = '/x'): Request
    {
        $request = Request::create($url, 'GET');

        $route = new IlluminateRoute('GET', '/x', static fn () => null);
        $route->bind($request);
        foreach ($params as $name => $value) {
            $route->setParameter($name, $value);
        }
        $request->setRouteResolver(static fn () => $route);

        return $request;
    }

    private function runMiddleware(Request $request): void
    {
        $this->middleware->handle($request, static fn ($req) => response('ok'));
    }

    private function assertResolvesTeam(Request $request, int $expectedFundId): void
    {
        setPermissionsTeamId(self::SENTINEL_TEAM_ID);
        $this->runMiddleware($request);
        $this->assertSame($expectedFundId, getPermissionsTeamId());
    }

    private function assertResolvesNothing(Request $request): void
    {
        setPermissionsTeamId(self::SENTINEL_TEAM_ID);
        $this->runMiddleware($request);
        // Untouched: the middleware only sets the team when it finds a fund.
        $this->assertSame(self::SENTINEL_TEAM_ID, getPermissionsTeamId());
    }
}
