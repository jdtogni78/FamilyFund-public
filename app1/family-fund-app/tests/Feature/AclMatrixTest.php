<?php

namespace Tests\Feature;

use App\Models\AccountExt;
use App\Models\Fund;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\TestFixtures;
use Tests\TestCase;

/**
 * Discoverable ACL matrix.
 *
 * Walks every GET route protected by the auth middleware, fires it as
 * each named role (and anonymous), and records the HTTP status. The
 * result is diffed against tests/golden/acl_matrix.json.
 *
 * On first run (or with ACL_MATRIX_UPDATE=1) the golden file is
 * written instead of compared, so adding a route is a one-step refresh.
 *
 * Coverage strategy:
 *   - Use an existing fund from the dev DB so routes that read related
 *     transactions / portfolios / etc. don't 500 from missing data.
 *   - Attach the beneficiary user to that same fund via a fresh test
 *     account, so per-user scoping ("view own") still has something to
 *     match. The transaction wraps & rolls back so the dev DB is
 *     untouched.
 *   - Resolve param placeholders ({account}, {portfolio}, {transaction}
 *     etc.) by querying the dev DB for the first existing row. If a
 *     table is empty the resolver returns null and routes that need
 *     that param are reported under "skipped".
 *
 * Routes are skipped (and reported) when:
 *   - A required URI param has no resolver in $paramMap.
 *   - A resolver returned null (e.g. table empty or missing).
 *
 * If the dev DB has no funds at all the whole test is skipped.
 */
class AclMatrixTest extends TestCase
{
    use DatabaseTransactions;

    private const GOLDEN_PATH = __DIR__ . '/../golden/acl_matrix.json';
    private const AUTH_MIDDLEWARE = 'auth';

    /** @var array<string,callable():mixed> */
    private array $paramMap;

    /** @var array<string,?\App\Models\User> */
    private array $actors;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Reuse an existing dev-DB fund so related-data lookups
        // (transactions, portfolios, reports) don't 500.
        $fund = Fund::query()->orderBy('id')->first();
        if ($fund === null) {
            $this->markTestSkipped('AclMatrixTest needs at least one fund in the DB.');
        }

        $users = TestFixtures::aclUsers($fund);

        $this->actors = [
            'anonymous'        => null,
            'unassigned'       => $users['unassigned'],
            'beneficiary'      => $users['beneficiary'],
            'financialManager' => $users['financialManager'],
            'fundAdmin'        => $users['fundAdmin'],
            'systemAdmin'      => $users['systemAdmin'],
        ];

        $beneficiaryAccount = $users['beneficiaryAccount'];

        // First-row-from-DB resolver helper.
        $first = fn(string $table, string $col = 'id') => fn() => DB::table($table)->value($col);

        // Resolvers for {param} placeholders. Add new entries as the
        // route surface grows. Anything that returns null is reported
        // under "skipped" rather than blowing up.
        // Prefer an existing account from the dev DB for {account}: it has
        // transactions, balances, etc. so show/report routes don't 500 on
        // missing data. Fall back to the fixture account if the table is
        // empty (CI / fresh DB).
        $accountId = DB::table('accounts')->where('fund_id', $fund->id)->value('id')
                  ?? $beneficiaryAccount->id;

        $this->paramMap = [
            // Synthetic / fixture-owned
            'fund'        => fn() => $fund->id,
            'account'     => fn() => $accountId,
            'as_of'       => fn() => '2024-01-01',
            'date'        => fn() => '2024-01-01',
            'asOf'        => fn() => '2024-01-01',
            'start'       => fn() => '2024-01-01',
            'end'         => fn() => '2024-12-31',

            // Generic {id} - default to the fund (most common usage)
            'id'          => fn() => $fund->id,

            // Look these up from existing rows in the dev DB
            'portfolio'        => $first('portfolios'),
            'transaction'      => $first('transactions'),
            'asset'            => $first('assets'),
            'assetPrice'       => $first('asset_prices'),
            'assetChangeLog'   => $first('asset_change_logs'),
            'accountBalance'   => $first('account_balances'),
            'accountGoal'      => $first('account_goals'),
            'accountMatchingRule' => $first('account_matching_rules'),
            'account_balance'  => $first('account_balances'),
            'account_goal'     => $first('account_goals'),
            'account_matching_rule' => $first('account_matching_rules'),
            'accountReport'    => $first('account_reports'),
            'account_report'   => $first('account_reports'),
            'matchingRule'     => $first('matching_rules'),
            'matching_rule'    => $first('matching_rules'),
            'tradePortfolio'   => $first('trade_portfolios'),
            'trade_portfolio'  => $first('trade_portfolios'),
            'tradePortfolioItem' => $first('trade_portfolio_items'),
            'trade_portfolio_item' => $first('trade_portfolio_items'),
            'tradeBandReport'  => $first('trade_band_reports'),
            'trade_band_report' => $first('trade_band_reports'),
            'fundReport'       => $first('fund_reports'),
            'fund_report'      => $first('fund_reports'),
            'transactionMatching' => $first('transaction_matchings'),
            'transaction_matching' => $first('transaction_matchings'),
            'goal'             => $first('goals'),
            'user'             => fn() => $beneficiaryAccount->user_id,
            'schedule'         => $first('schedules'),
            'scheduledJob'     => $first('scheduled_jobs'),
            'scheduled_job'    => $first('scheduled_jobs'),
            'portfolioAsset'   => $first('portfolio_assets'),
            'portfolio_asset'  => $first('portfolio_assets'),
        ];
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_acl_matrix_matches_golden(): void
    {
        $rows = [];
        $skipped = [];

        foreach (Route::getRoutes() as $route) {
            if (!in_array('GET', $route->methods(), true)) {
                continue;
            }
            $middleware = $route->gatherMiddleware();
            if (!in_array(self::AUTH_MIDDLEWARE, $middleware, true)) {
                continue;
            }
            if (in_array('api', $middleware, true)) {
                continue;
            }

            $uri = $route->uri();
            [$resolved, $reason] = $this->resolveUri($uri);
            if ($resolved === null) {
                $skipped[$uri] = $reason;
                continue;
            }

            $rows[$uri] = [];
            foreach ($this->actors as $name => $user) {
                $rows[$uri][$name] = $this->statusFor($user, $resolved);
            }
        }

        ksort($rows);
        ksort($skipped);
        $actual = ['routes' => $rows, 'skipped' => $skipped];

        if (!file_exists(self::GOLDEN_PATH) || getenv('ACL_MATRIX_UPDATE') === '1') {
            @mkdir(dirname(self::GOLDEN_PATH), 0777, true);
            file_put_contents(self::GOLDEN_PATH, json_encode($actual, JSON_PRETTY_PRINT) . "\n");
            $this->markTestSkipped('Wrote ACL matrix golden file at ' . self::GOLDEN_PATH);
        }

        $expected = json_decode(file_get_contents(self::GOLDEN_PATH), true);
        $this->assertSame(
            $expected,
            $actual,
            'ACL matrix drifted from golden. Re-run with ACL_MATRIX_UPDATE=1 to accept.'
        );
    }

    /**
     * Replace {param} and {param?} segments using $paramMap.
     * Returns [resolvedUri|null, skipReason|null].
     */
    private function resolveUri(string $uri): array
    {
        $skipReason = null;
        $out = preg_replace_callback('/\{(\w+)(\??)\}/', function ($m) use (&$skipReason) {
            [$_, $name, $optional] = $m;
            if (isset($this->paramMap[$name])) {
                $value = ($this->paramMap[$name])();
                if ($value === null || $value === '') {
                    if ($optional === '?') {
                        return '';
                    }
                    $skipReason = $skipReason ?? "no-data-for:{$name}";
                    return $m[0];
                }
                return rawurlencode((string) $value);
            }
            if ($optional === '?') {
                return '';
            }
            $skipReason = $skipReason ?? "unresolved-param:{$name}";
            return $m[0];
        }, $uri);

        if ($skipReason !== null) {
            return [null, $skipReason];
        }
        return ['/' . trim($out, '/'), null];
    }

    private function statusFor(?\App\Models\User $user, string $uri): int
    {
        // Reset auth between requests so "anonymous" really is anonymous
        // and one role doesn't bleed into the next.
        auth()->forgetGuards();
        if ($user) {
            $this->actingAs($user);
        }

        try {
            return $this->get($uri)->status();
        } catch (\Throwable $e) {
            return 500;
        }
    }
}
