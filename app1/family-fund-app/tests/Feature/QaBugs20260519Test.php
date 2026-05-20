<?php

namespace Tests\Feature;

use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\QaTestUsersSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Regression coverage for the open items in docs/QA_BUGS_2026-05-19.md.
 *
 * One test per bug number; each one asserts the post-fix behavior so the
 * file as a whole serves as the reproducer and the guard against regression.
 */
class QaBugs20260519Test extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $df;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->df = new DataFactory();
        $this->df->createFund(1_000_000, 1_000_000, '2020-01-01');
        $this->df->createUser();
        $this->admin = $this->df->user;

        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->admin->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    /**
     * Convenience: insert a minimal credit-line row directly (sidesteps the
     * draw service) just so the listing has data to render.
     */
    private function seedCreditLine(int $accountId, array $overrides = []): AccountCreditLine
    {
        return AccountCreditLine::create(array_merge([
            'account_id'         => $accountId,
            'nickname'           => 'qa-bug-line',
            'principal_shares'   => 3_000,
            'outstanding_shares' => 2_766.6667,
            'term_months'        => 12,
            'origination_date'   => '2024-01-01',
            'maturity_date'      => '2025-01-01',
            'payment_frequency'  => 'monthly',
            'status'             => 'active',
        ], $overrides));
    }

    // ---- #10 "#" column should be a row index, not the DB id ------------

    public function test_bug_10_credit_lines_index_uses_row_index_not_db_id(): void
    {
        $account = $this->df->userAccount;

        // Pre-seed multiple lines so the table has >1 row; force a non-1 id
        // by creating + deleting throwaway rows first so we know the first
        // row's id is unequal to its position.
        $throwaway = $this->seedCreditLine($account->id, ['nickname' => 'throw']);
        $throwaway->delete();

        $first  = $this->seedCreditLine($account->id, ['nickname' => 'first-line']);
        $second = $this->seedCreditLine($account->id, ['nickname' => 'second-line']);

        $this->assertGreaterThan(
            1,
            $first->id,
            'pre-condition: first line id should be > 1 so we can distinguish row-index from db-id'
        );

        $response = $this->actingAs($this->admin)->get(route('credit_lines.global_index'));
        $response->assertOk();

        $body = $response->getContent();
        $needle = '<td>' . $first->id . '</td>';
        $this->assertStringNotContainsString(
            $needle,
            $body,
            "#10: the '#' column on /credit-lines should not echo the DB id ({$first->id}). Expected 1-based row index."
        );

        // The "active first" ordering means rows render newest-id first.
        // The order doesn't matter for this assertion — only that some <td>
        // before the nickname has the loop index 1.
        $this->assertMatchesRegularExpression(
            '/<td>\s*1\s*<\/td>\s*<td>\s*(first-line|second-line)/',
            $body,
            '#10: the first data row should show "1" in the "#" column.'
        );
    }

    // ---- #11 money formatting on /credit-lines --------------------------

    public function test_bug_11_credit_lines_index_formats_amounts_to_two_decimals(): void
    {
        $account = $this->df->userAccount;
        $this->seedCreditLine($account->id, [
            'principal_shares'   => 3_000.0,
            'outstanding_shares' => 2_766.6667,
        ]);

        $response = $this->actingAs($this->admin)->get(route('credit_lines.global_index'));
        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringNotContainsString(
            '3,000.0000',
            $body,
            '#11: principal should not render with 4 decimal places.'
        );
        $this->assertStringNotContainsString(
            '2,766.6667',
            $body,
            '#11: outstanding should not render with 4 decimal places.'
        );
        $this->assertStringContainsString('3,000.00', $body, '#11: principal should render with 2dp.');
        $this->assertStringContainsString('2,766.67', $body, '#11: outstanding should render with 2dp (rounded).');
    }

    // ---- #12 goal Active Period drops time component --------------------

    public function test_bug_12_goal_show_active_period_has_no_time_component(): void
    {
        $account = $this->df->userAccount;
        $goal = $this->df->createGoal(
            $account,
            \App\Models\GoalExt::TARGET_TYPE_TOTAL,
            10_000,
            0.04,
            0,
            Carbon::parse('2021-03-01'),
            Carbon::parse('2030-01-01')
        );

        $response = $this->actingAs($this->admin)->get(route('goals.show', $goal->id));
        $response->assertOk();
        $body = $response->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/\b\d{4}-\d{2}-\d{2}\s+00:00:00\b/',
            $body,
            '#12: goal show page must not render dates with trailing 00:00:00.'
        );
        $this->assertStringContainsString('2021-03-01', $body);
        $this->assertStringContainsString('2030-01-01', $body);
    }

    // ---- #13 rebalance form: "Forever" button has no "00" prefix --------

    public function test_bug_13_rebalance_form_forever_button_has_no_double_zero(): void
    {
        // Render the rebalance form blade directly with the same view args
        // the controller passes. The controller path (annotateAssetsAndGroups)
        // requires asset-price data we don't seed here, so we bypass it.
        $tp = $this->df->createTradePortfolio(Carbon::today()->subYear()->toDateString(), '9999-12-31');

        // The layout reads auth()->user()->name, so we need an authed user.
        $this->actingAs($this->admin);

        // Share an empty MessageBag so the flash-messages partial doesn't
        // blow up on $errors. (Normally Laravel's ShareErrorsFromSession
        // middleware injects this for HTTP requests.)
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());

        $body = view('trade_portfolios.rebalance', [
            'assetMap'       => [],
            'typeMap'        => [],
            'tradePortfolio' => $tp,
            'items'          => collect(),
            'start_dt'       => Carbon::today()->toDateString(),
            'end_dt'         => '9999-12-31',
        ])->render();

        $this->assertMatchesRegularExpression(
            '/id=["\']set_never_end["\']/',
            $body,
            '#13: rebalance form should still expose the Forever button.'
        );
        $this->assertDoesNotMatchRegularExpression(
            // The literal "00Forever" or "OOForever" rendered when the
            // infinity glyph fell back to fallback font. The fix replaces
            // the fa-infinity <i> with a plain unicode ∞ + " Forever".
            '/00\s*Forever|OO\s*Forever/i',
            $body,
            '#13: the Forever button label must not start with "00" / "OO".'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<i[^>]*fa-infinity[^>]*><\/i>\s*Forever/',
            $body,
            '#13: stop using fa fa-infinity (not in this FA version); use ∞ unicode glyph instead.'
        );
    }

    // ---- #9 /credit-lines/payments page heading matches URL -------------

    public function test_bug_9_global_payments_heading_matches_payments_url(): void
    {
        $response = $this->actingAs($this->admin)->get(route('credit_lines.global_payments'));
        $response->assertOk();
        $body = $response->getContent();

        // The URL says "payments" — the heading should too.
        $this->assertStringContainsString(
            'Payments',
            $body,
            '#9: /credit-lines/payments page must mention "Payments" in the heading.'
        );
        $this->assertStringNotContainsString(
            'Receivables — all accounts',
            $body,
            '#9: legacy "Receivables — all accounts" heading must be removed.'
        );
    }

    // ---- #7 dashboard nav hides links a user has no permission to ------

    public function test_bug_7_dashboard_nav_hides_unreachable_links_for_no_role_user(): void
    {
        // claude@test.local in dev is a user with no roles / no person —
        // simulate the same here, but with a verified email so the dashboard
        // route (auth + verified) actually renders the menu.
        $noRole = User::factory()->create([
            'email' => 'no-role-' . uniqid() . '@test.local',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($noRole)->get('/dashboard');
        $response->assertOk();
        $body = $response->getContent();

        // Sanity: dashboard chrome is present so we know the menu rendered.
        $this->assertStringContainsString('Dashboard', $body, '#7: dashboard must render for a verified user.');

        // Pre-fix: every nav link rendered regardless of permission, so a
        // no-role user could click /funds and /credit-lines and only then
        // discover the 403. Post-fix: those links must not be in the
        // rendered dashboard for a no-role user.
        $unreachableLinks = [
            route('funds.index'),
            route('accounts.index'),
            route('transactions.index'),
            route('credit_lines.global_index'),
            route('tradePortfolios.index'),
            route('fundReports.index'),
        ];
        foreach ($unreachableLinks as $link) {
            $this->assertStringNotContainsString(
                'href="' . $link . '"',
                $body,
                "#7: nav must hide {$link} from a no-role user (they 403 on click)."
            );
        }
    }

    // ---- #8 error pages include the app chrome --------------------------

    public function test_bug_8_403_error_page_includes_app_chrome(): void
    {
        // The credit_lines.global_index endpoint aborts 403 for non-admin
        // authenticated users — a good 403 fixture.
        $intruder = User::factory()->create([
            'email' => 'intruder-' . uniqid() . '@test.local',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($intruder)->get(route('credit_lines.global_index'));
        $response->assertForbidden();
        $body = $response->getContent();

        $this->assertStringContainsString(
            'Back to dashboard',
            $body,
            '#8: 403 page should include a "Back to dashboard" link.'
        );
    }

    public function test_bug_8_404_error_page_includes_app_chrome(): void
    {
        $response = $this->actingAs($this->admin)->get('/this-route-does-not-exist-' . uniqid());
        $response->assertNotFound();
        $body = $response->getContent();

        $this->assertStringContainsString(
            'Back to dashboard',
            $body,
            '#8: 404 page should include a "Back to dashboard" link.'
        );
    }

    /**
     * Unauthenticated 404 must not bubble up to a 500 because the
     * navigation partial dereferences a null `auth()->user()`. We hit a
     * bogus route with no actingAs() and expect a clean 404 + chrome.
     */
    public function test_bug_8_404_renders_cleanly_for_unauthenticated_requests(): void
    {
        $response = $this->get('/this-route-does-not-exist-' . uniqid());
        $response->assertNotFound();
        $this->assertStringContainsString(
            'Back to dashboard',
            $response->getContent(),
            '#8: unauthenticated 404 must still render the chromed error page (no 500 fallback).'
        );
    }

    // ---- #2 + #5 prod_to_dev wrapper runs the missing artisan steps -----

    public function test_bug_2_and_5_prod_to_dev_wrapper_runs_migrate_and_seeders(): void
    {
        $wrapper = base_path('bin/prod-to-dev.sh');
        $this->assertFileExists(
            $wrapper,
            '#2/#5: expected a bin/prod-to-dev.sh wrapper that runs the SQL plus pending migrations + seeders.'
        );

        $contents = file_get_contents($wrapper);
        $this->assertStringContainsString(
            'prod_to_dev.sql',
            $contents,
            '#2/#5: wrapper must apply the anonymization SQL.'
        );
        $this->assertMatchesRegularExpression(
            '/artisan\s+migrate\s+--force/',
            $contents,
            '#2: wrapper must run `php artisan migrate --force` to apply pending dev migrations.'
        );
        $this->assertMatchesRegularExpression(
            '/db:seed\s+--class=RolesAndPermissionsSeeder/',
            $contents,
            '#5: wrapper must seed RolesAndPermissionsSeeder.'
        );
        $this->assertMatchesRegularExpression(
            '/db:seed\s+--class=QaTestUsersSeeder/',
            $contents,
            '#5/#14: wrapper must seed QaTestUsersSeeder so qa-* + claude users exist.'
        );
    }

    // ---- #14 claude@test.local has a role + verified email --------------

    public function test_bug_14_claude_test_user_is_role_assigned_after_seeders(): void
    {
        // Re-run the prod_to_dev claude@test.local insert (idempotent) and
        // QaTestUsersSeeder. Then assert claude@test.local has a viewable
        // role + verified email.
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(QaTestUsersSeeder::class);

        $claude = User::where('email', 'claude@test.local')->first();
        $this->assertNotNull($claude, '#14: claude@test.local must exist after seeders.');
        $this->assertNotNull(
            $claude->email_verified_at,
            '#14: claude@test.local must have email_verified_at set.'
        );

        // Spatie role assignment is team-scoped (fund_id). Query the join
        // table directly so we don't depend on the active team id.
        $roleCount = \DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $claude->id)
            ->whereIn('model_has_roles.model_type', [User::class, \App\Models\UserExt::class])
            ->count();
        $this->assertGreaterThan(
            0,
            $roleCount,
            '#14: claude@test.local must have at least one role assigned (any fund).'
        );
    }
}
