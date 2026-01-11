<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Smoke tests to verify major pages render without errors.
 * These tests visit routes and verify they return 200 status code.
 *
 * Note: Some pages require complex data setup and are tested separately
 * in their respective feature tests.
 */
class SmokeTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        $this->user = $this->factory->user;

        // Give user system-admin role for smoke tests (fund_id=0 for global access)
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->user->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);
    }

    protected function tearDown(): void
    {
        // Clean up any unclosed output buffers from Livewire/Blade rendering
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Dashboard & Profile ====================

    public function test_dashboard_renders()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_profile_renders()
    {
        $response = $this->actingAs($this->user)->get('/profile');
        $response->assertStatus(200);
    }

    // ==================== Index Pages (List Views) ====================

    public function test_funds_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/funds');
        $response->assertStatus(200);
    }

    public function test_accounts_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/accounts');
        $response->assertStatus(200);
    }

    public function test_transactions_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/transactions');
        $response->assertStatus(200);
    }

    public function test_portfolios_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/portfolios');
        $response->assertStatus(200);
    }

    public function test_trade_portfolios_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/tradePortfolios');
        $response->assertStatus(200);
    }

    public function test_assets_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/assets');
        $response->assertStatus(200);
    }

    public function test_asset_prices_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/assetPrices');
        $response->assertStatus(200);
    }

    public function test_goals_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/goals');
        $response->assertStatus(200);
    }

    public function test_account_balances_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/accountBalances');
        $response->assertStatus(200);
    }

    public function test_fund_reports_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/fundReports');
        $response->assertStatus(200);
    }

    public function test_account_reports_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/accountReports');
        $response->assertStatus(200);
    }

    public function test_matching_rules_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/matchingRules');
        $response->assertStatus(200);
    }

    public function test_account_matching_rules_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/accountMatchingRules');
        $response->assertStatus(200);
    }

    public function test_schedules_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/schedules');
        $response->assertStatus(200);
    }

    public function test_scheduled_jobs_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/scheduledJobs');
        $response->assertStatus(200);
    }

    public function test_users_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/users');
        $response->assertStatus(200);
    }

    public function test_people_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/people');
        $response->assertStatus(200);
    }

    public function test_cash_deposits_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/cashDeposits');
        $response->assertStatus(200);
    }

    public function test_deposit_requests_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/depositRequests');
        $response->assertStatus(200);
    }

    // Note: portfolioReports route does not exist in web.php

    public function test_change_logs_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/changeLogs');
        $response->assertStatus(200);
    }

    public function test_addresses_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/addresses');
        $response->assertStatus(200);
    }

    public function test_phones_index_renders()
    {
        $response = $this->actingAs($this->user)->get('/phones');
        $response->assertStatus(200);
    }

    // ==================== Create Pages ====================

    public function test_funds_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/funds/create');
        $response->assertStatus(200);
    }

    public function test_accounts_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/accounts/create');
        $response->assertStatus(200);
    }

    public function test_transactions_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/transactions/create');
        $response->assertStatus(200);
    }

    public function test_transactions_create_bulk_renders()
    {
        $response = $this->actingAs($this->user)->get('/transactions/create_bulk');
        $response->assertStatus(200);
    }

    public function test_assets_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/assets/create');
        $response->assertStatus(200);
    }

    public function test_goals_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/goals/create');
        $response->assertStatus(200);
    }

    public function test_fund_reports_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/fundReports/create');
        $response->assertStatus(200);
    }

    public function test_matching_rules_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/matchingRules/create');
        $response->assertStatus(200);
    }

    public function test_account_matching_rules_create_bulk_renders()
    {
        $account = $this->factory->userAccount;
        $response = $this->actingAs($this->user)->get('/accountMatchingRules/create_bulk?account=' . $account->id);
        $response->assertStatus(200);
    }

    public function test_schedules_create_renders()
    {
        $response = $this->actingAs($this->user)->get('/schedules/create');
        $response->assertStatus(200);
    }

    // ==================== Show/Detail Pages ====================

    public function test_users_show_renders()
    {
        $response = $this->actingAs($this->user)->get('/users/' . $this->user->id);
        $response->assertStatus(200);
    }

    public function test_portfolios_show_renders()
    {
        $response = $this->actingAs($this->user)->get('/portfolios/' . $this->factory->portfolio->id);
        $response->assertStatus(200);
    }

    public function test_accounts_edit_renders()
    {
        $account = $this->factory->userAccount;
        $response = $this->actingAs($this->user)->get('/accounts/' . $account->id . '/edit');
        $response->assertStatus(200);

        // Verify form fields are populated with account data
        $response->assertSee('value="' . $account->code . '"', false);
        $response->assertSee('value="' . $account->nickname . '"', false);
    }

    public function test_funds_edit_renders()
    {
        $fund = $this->factory->fund;
        $response = $this->actingAs($this->user)->get('/funds/' . $fund->id . '/edit');
        $response->assertStatus(200);

        // Verify form fields are populated with fund data
        $response->assertSee('value="' . $fund->name . '"', false);
    }

    // ==================== Other Pages ====================

    public function test_change_password_renders()
    {
        $response = $this->actingAs($this->user)->get('/change-password');
        $response->assertStatus(200);
    }

    // ==================== Auth Pages (no login required) ====================

    public function test_login_page_renders()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_register_page_renders()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_password_request_page_renders()
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
    }

    // ==================== Dynamic Route Discovery ====================

    /**
     * Routes to skip in discovery test (require special setup or are known issues).
     * Add routes here that need complex setup beyond parameter resolution.
     */
    private function getSkippedRoutes(): array
    {
        return [
            // Two-factor auth requires session state
            'two-factor',
            'two-factor/recovery-codes',
            'two-factor/setup',

            // Admin routes may require additional setup
            'admin/user-roles',
            'admin/user-roles/{user}/edit',

            // These create/edit forms need controller data passed via 'api' variable
            // They are tested explicitly in separate tests
            'accountMatchingRules/create',
            'accountMatchingRules/{accountMatchingRule}/edit',
            'accountReports/{accountReport}/edit',
            'persons/create',
            'persons/{person}/edit',
            'people/create',
            'people/{person}/edit',
            'id_documents/create',
            'transactions/{transaction}/edit',

            // Account show uses extended view with complex data
            'accounts/{account}',

            // Dev-only routes
            'dev-login/{redirect?}',
        ];
    }

    /**
     * Route overrides for special query parameters or path modifications.
     * Format: 'route_pattern' => ['query' => [...], 'skip' => false]
     */
    private function getRouteOverrides(): array
    {
        return [
            'accountMatchingRules/create_bulk' => [
                'query' => ['account' => $this->factory->userAccount->id],
            ],
            'transactions/create_bulk' => [
                'query' => ['fund_id' => $this->factory->fund->id],
            ],
        ];
    }

    /**
     * Automatically discover and test all GET web routes.
     * This test finds routes, resolves parameters from factory/database,
     * and verifies each returns 200 or 302.
     */
    public function test_all_discovered_routes_render()
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $tested = [];
        $skipped = [];
        $failed = [];

        // Parameter resolvers - maps route parameter names to values
        $paramResolvers = $this->getParameterResolvers();
        $skippedRoutes = $this->getSkippedRoutes();
        $routeOverrides = $this->getRouteOverrides();

        foreach ($routes as $route) {
            // Only test GET routes
            if (!in_array('GET', $route->methods())) {
                continue;
            }

            $uri = $route->uri();

            // Skip API routes
            if (str_starts_with($uri, 'api/') || str_starts_with($uri, 'api.')) {
                continue;
            }

            // Skip livewire internal routes
            if (str_starts_with($uri, 'livewire/')) {
                continue;
            }

            // Skip sanctum routes
            if (str_starts_with($uri, 'sanctum/')) {
                continue;
            }

            // Check if route should be skipped
            if (in_array($uri, $skippedRoutes)) {
                $skipped[] = $uri . ' (explicit skip)';
                continue;
            }

            // Try to resolve parameters
            $resolvedUri = $this->resolveRouteParameters($uri, $paramResolvers);

            if ($resolvedUri === null) {
                $skipped[] = $uri . ' (unresolved params)';
                continue;
            }

            // Build the full URL with any query overrides
            $queryString = '';
            if (isset($routeOverrides[$uri]['query'])) {
                $queryString = '?' . http_build_query($routeOverrides[$uri]['query']);
            }

            // Test the route
            try {
                $response = $this->actingAs($this->user)->get('/' . ltrim($resolvedUri, '/') . $queryString);
                $status = $response->status();

                if (in_array($status, [200, 302, 301])) {
                    $tested[] = ['uri' => $resolvedUri, 'status' => $status];
                } else {
                    // Try to extract error message from response
                    $error = '';
                    if ($status === 500) {
                        $content = $response->getContent();
                        if (preg_match('/Exception.*?:(.*?)(?:\n|<)/s', $content, $m)) {
                            $error = trim($m[1]);
                        } elseif (preg_match('/error["\']?\s*:\s*["\']([^"\']+)/i', $content, $m)) {
                            $error = trim($m[1]);
                        }
                    }
                    $failed[] = ['uri' => $resolvedUri, 'status' => $status, 'original' => $uri, 'error' => $error];
                }
            } catch (\Exception $e) {
                $failed[] = ['uri' => $resolvedUri, 'status' => 'exception', 'error' => $e->getMessage(), 'original' => $uri];
            }
        }

        // Output summary
        $this->addToAssertionCount(count($tested));

        if (!empty($failed)) {
            $failMessages = array_map(function ($f) {
                $msg = "Route '{$f['uri']}' (pattern: {$f['original']}) returned {$f['status']}";
                if (isset($f['error'])) {
                    $msg .= ": " . substr($f['error'], 0, 100);
                }
                return $msg;
            }, $failed);

            $this->fail(
                "Route discovery found " . count($failed) . " failing route(s):\n" .
                implode("\n", $failMessages) .
                "\n\nTested: " . count($tested) . ", Skipped: " . count($skipped)
            );
        }

        // If we get here, all routes passed
        $this->assertTrue(true, "Tested " . count($tested) . " routes, skipped " . count($skipped));
    }

    /**
     * Build parameter resolvers from factory and database.
     */
    private function getParameterResolvers(): array
    {
        // Create additional test data if needed
        $this->factory->createMatching();

        return [
            // From factory
            'fund' => $this->factory->fund->id,
            'account' => $this->factory->userAccount->id,
            'user' => $this->user->id,
            'portfolio' => $this->factory->portfolio->id,
            'transaction' => $this->factory->transaction->id ?? $this->createTransaction(),
            'matchingRule' => $this->factory->matchingRule->id ?? null,
            'matching_rule' => $this->factory->matchingRule->id ?? null,

            // Try to get from database if not in factory
            'asset' => \App\Models\Asset::first()?->id,
            'assetPrice' => \App\Models\AssetPrice::first()?->id,
            'asset_price' => \App\Models\AssetPrice::first()?->id,
            'goal' => \App\Models\Goal::first()?->id,
            'tradePortfolio' => \App\Models\TradePortfolio::first()?->id,
            'trade_portfolio' => \App\Models\TradePortfolio::first()?->id,
            'schedule' => \App\Models\Schedule::first()?->id,
            'scheduledJob' => \App\Models\ScheduledJob::first()?->id,
            'scheduled_job' => \App\Models\ScheduledJob::first()?->id,
            'person' => \App\Models\Person::first()?->id,
            'accountBalance' => \App\Models\AccountBalance::first()?->id,
            'account_balance' => \App\Models\AccountBalance::first()?->id,
            'fundReport' => \App\Models\FundReport::first()?->id,
            'fund_report' => \App\Models\FundReport::first()?->id,
            'accountReport' => \App\Models\AccountReport::first()?->id,
            'account_report' => \App\Models\AccountReport::first()?->id,
            'accountMatchingRule' => \App\Models\AccountMatchingRule::first()?->id,
            'account_matching_rule' => \App\Models\AccountMatchingRule::first()?->id,
            'cashDeposit' => \App\Models\CashDeposit::first()?->id,
            'cash_deposit' => \App\Models\CashDeposit::first()?->id,
            'depositRequest' => \App\Models\DepositRequest::first()?->id,
            'deposit_request' => \App\Models\DepositRequest::first()?->id,
            'changeLog' => \App\Models\ChangeLog::first()?->id,
            'change_log' => \App\Models\ChangeLog::first()?->id,
            'address' => \App\Models\Address::first()?->id,
            'phone' => \App\Models\Phone::first()?->id,
            'transactionMatching' => \App\Models\TransactionMatching::first()?->id,
            'transaction_matching' => \App\Models\TransactionMatching::first()?->id,
            'portfolioAsset' => \App\Models\PortfolioAsset::first()?->id,
            'portfolio_asset' => \App\Models\PortfolioAsset::first()?->id,
            'tradePortfolioItem' => \App\Models\TradePortfolioItem::first()?->id,
            'trade_portfolio_item' => \App\Models\TradePortfolioItem::first()?->id,
            'accountGoal' => \App\Models\AccountGoal::first()?->id,
            'account_goal' => \App\Models\AccountGoal::first()?->id,
            'idDocument' => \App\Models\IdDocument::first()?->id,
            'id_document' => \App\Models\IdDocument::first()?->id,
        ];
    }

    /**
     * Create a transaction if none exists.
     */
    private function createTransaction(): ?int
    {
        if ($this->factory->transaction) {
            return $this->factory->transaction->id;
        }
        // Create a simple transaction
        $tx = \App\Models\Transaction::create([
            'account_id' => $this->factory->userAccount->id,
            'type' => 'INI',
            'status' => 'C',
            'value' => 100,
            'timestamp' => now(),
        ]);
        return $tx->id;
    }

    /**
     * Resolve route parameters using the resolvers.
     * Returns null if any required parameter cannot be resolved.
     */
    private function resolveRouteParameters(string $uri, array $resolvers): ?string
    {
        // Find all parameters in the URI
        preg_match_all('/\{(\w+)\??}/', $uri, $matches);

        if (empty($matches[1])) {
            // No parameters, return as-is
            return $uri;
        }

        $resolved = $uri;
        foreach ($matches[1] as $param) {
            $isOptional = str_contains($uri, '{' . $param . '?}');

            // Try to find a resolver
            $value = $resolvers[$param] ?? $resolvers[$this->snakeToCamel($param)] ?? $resolvers[$this->camelToSnake($param)] ?? null;

            if ($value === null) {
                if ($isOptional) {
                    // Remove optional parameter from URI
                    $resolved = preg_replace('/\/?\{' . $param . '\?\}/', '', $resolved);
                } else {
                    // Required parameter not found
                    return null;
                }
            } else {
                // Replace parameter with value
                $resolved = str_replace(['{' . $param . '}', '{' . $param . '?}'], $value, $resolved);
            }
        }

        return $resolved;
    }

    private function snakeToCamel(string $str): string
    {
        return lcfirst(str_replace('_', '', ucwords($str, '_')));
    }

    private function camelToSnake(string $str): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $str));
    }
}
