<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountCreditLine;
use App\Models\AccountMatchingRule;
use App\Models\AccountReport;
use App\Models\Asset;
use App\Models\AssetChangeLog;
use App\Models\AssetPrice;
use App\Models\CreditLinePayment;
use App\Models\Person;
use App\Models\TradeBandReport;
use App\Models\TradePortfolio;
use App\Models\TradePortfolioItem;
use App\Models\TransactionExt;
use App\Models\TransactionMatching;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Tests\DataFactory;

/**
 * Synthetic test-baseline seeder (replaces the committed real-data dump
 * database/test/test-baseline.sql.gz — see issue #78 / SECRET-ROTATION-PLAN §5).
 *
 * Builds, from scratch and with NO real PII, the database state the suite
 * expects to find pre-loaded. The vast majority of tests create their own data
 * via Tests\DataFactory inside a DatabaseTransactions rollback, so they don't
 * read this baseline at all; the baseline only has to satisfy the handful of
 * fixtures that read pre-existing rows:
 *
 *   - AclMatrixTest needs *a fund to exist* (it reseeds roles + builds its own
 *     role-fixture users in setUp), plus — for the nightly full sweep — one
 *     first-row entity in each table its {param} resolvers look up.
 *   - The credit-line Browser/Dusk tests log in as claude@test.local (seeded by
 *     QaTestUsersSeeder) and hard-code account id 7 owned by
 *     user1@dev.familyfund.local, expecting a large OWN balance to borrow
 *     against. (Dusk is not part of the `php artisan test` CI gate, but testpool
 *     `tour` restores from this same baseline, so we keep account 7 working.)
 *   - A couple of controller tests do `User::find(1)` (with a firstOrCreate
 *     fallback), so user id 1 is provided.
 *
 * Determinism: a fixed Faker seed + explicit values on every load-bearing field
 * keep the baseline stable run-to-run (so the ACL golden and any value-reading
 * test don't drift between CI runs).
 *
 * This is a test/dev fixture only — it must never run in production. Wired into
 * CI (.github/workflows/tests.yml) and ~/.familyfund-pool/testpool.sh in place
 * of the old `gunzip … | mysql` dump restore.
 */
class TestBaselineSeeder extends Seeder
{
    /** Owner of the load-bearing account 7 (mirrors the old dump). */
    private const USER1_ID = 1;
    private const USER1_EMAIL = 'user1@dev.familyfund.local';

    /** Hard-coded by every credit-line Browser/Dusk test (ACCOUNT_ID = 7). */
    private const ACCOUNT7_ID = 7;
    private const ACCOUNT7_OWN_SHARES = 6000;

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('TestBaselineSeeder must never run in production.');
        }

        // Deterministic synthetic data: seed the shared Faker generator that all
        // factories resolve from the container.
        app(\Faker\Generator::class)->seed(20260524);

        // 1. Roles/permissions + offline reference data (holidays, sync schedule).
        $this->call([
            RolesAndPermissionsSeeder::class,
            ExchangeHolidaySeeder::class,
            HolidaySyncScheduleSeeder::class,
        ]);

        // 2. Reference assets + prices. getCashAsset()/getSP500Asset() THROW if
        //    absent and DataFactory->createFund() needs the CASH asset, so create
        //    them first. Countless tests build their own fund/portfolio (which
        //    holds a CASH position) but rely on the *baseline* for these shared
        //    reference prices, so give CASH/SPY a price row spanning all dates
        //    the suite asks about (a fund's NAV/share-price 500s without it).
        $cash = Asset::factory()->create(['name' => 'CASH', 'type' => 'CSH', 'source' => 'CASH']);
        $spy  = Asset::factory()->create(['name' => 'SPY', 'type' => 'STK', 'source' => 'IB']);
        // CASH: one wide price row (CASH is held in fund portfolios; NAV needs it).
        AssetPrice::factory()->create([
            'asset_id' => $cash->id, 'price' => 1.00,
            'start_dt' => '2000-01-01', 'end_dt' => '9999-12-31',
        ]);
        // SPY: a dense (every-3-days) price series. The scheduled fund-report
        // data-availability check is a *global* AssetPrice::whereBetween('start_dt',
        // [lookback, date]) query with a "≤5 trading days stale" fallback
        // (FundTrait::fundReportScheduleDue). The real dump carried ~daily prices
        // across years; this synthetic series gives the same "recent price exists
        // for any date" property the scheduled-report tests rely on.
        $now = now();
        $rows = [];
        for ($d = Carbon::parse('2020-12-01'), $end = Carbon::parse('2027-01-01'); $d->lte($end); $d->addDays(3)) {
            $rows[] = [
                'asset_id' => $spy->id, 'price' => 100.00,
                'start_dt' => $d->toDateString(),
                'end_dt'   => $d->copy()->addDays(2)->toDateString(),
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            AssetPrice::insert($chunk);
        }

        // 3. user1@dev.familyfund.local as user id 1 (load-bearing for Dusk +
        //    User::find(1)). Force the id so it matches the old dump.
        $person = Person::factory()->create([
            'first_name' => 'Test',
            'last_name'  => 'BeneficiaryOne',
            'email'      => 'person1@dev.familyfund.local',
        ]);
        Model::unguard();
        $user1 = User::factory()->make([
            'name'              => 'LUser1',
            'email'             => self::USER1_EMAIL,
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
            'person_id'         => $person->id,
        ]);
        $user1->id = self::USER1_ID;
        $user1->save();
        Model::reguard();

        // 4. The fund graph (fund + portfolio + fund account + initial
        //    transaction/balance + cash position). This becomes the lowest-id
        //    fund, so AclMatrixTest/QaTestUsersSeeder resolve to it.
        $df = new DataFactory();
        $fund = $df->createFund(shares: 1000, value: 1000, timestamp: '2022-01-01');
        $fund->name = 'Family Fund A';
        $fund->goal = 'Create generational wealth';
        $fund->save();

        // 5. Account 7, owned by user1, with a large OWN balance to borrow against.
        Model::unguard();
        $acct7 = Account::factory()->make([
            'code'     => 'A001',
            'nickname' => 'Acct7',
            'email_cc' => 'account7@dev.familyfund.local',
            'fund_id'  => $fund->id,
            'user_id'  => self::USER1_ID,
        ]);
        $acct7->id = self::ACCOUNT7_ID;
        $acct7->save();
        Model::reguard();

        // Initial deposit in early 2021 so account 7 carries an OWN balance
        // across the dates the account golden-data tests probe (2021-2022) and
        // today (Dusk borrowing). Two later cleared purchases give the
        // transactions_as_of endpoint a dated history to filter.
        $tran7 = $df->createTransaction(
            self::ACCOUNT7_OWN_SHARES * 100,
            $acct7,
            TransactionExt::TYPE_INITIAL,
            TransactionExt::STATUS_CLEARED,
            null,
            '2021-01-01'
        );
        $df->createBalance(self::ACCOUNT7_OWN_SHARES, $tran7, $acct7, '2021-01-01');
        $df->createTransaction(500 * 100, $acct7, TransactionExt::TYPE_PURCHASE, TransactionExt::STATUS_CLEARED, null, '2021-06-01');
        $df->createTransaction(500 * 100, $acct7, TransactionExt::TYPE_PURCHASE, TransactionExt::STATUS_CLEARED, null, '2022-01-10');

        // 6. Round out the graph so every AclMatrix {param} resolver and the
        //    index routes have ≥1 row to read in the nightly full sweep.
        $df->createAssetWithPrice(100.00);          // assets, asset_prices, portfolio_assets
        $df->createAssetWithPrice(50.00);
        AssetChangeLog::factory()->create();        // asset_change_logs

        $goal = $df->createGoal($acct7);            // goals + account_goals pivot

        $mr = $df->createMatchingRule();            // matching_rules
        AccountMatchingRule::factory()->create([    // account_matching_rules
            'account_id'       => $acct7->id,
            'matching_rule_id' => $mr->id,
        ]);

        // transactions + transaction_matchings
        $purchase = $df->createTransaction(100, $acct7, TransactionExt::TYPE_PURCHASE, TransactionExt::STATUS_CLEARED, null, '2022-02-01');
        $matching = $df->createTransaction(50, $acct7, TransactionExt::TYPE_MATCHING, TransactionExt::STATUS_CLEARED, null, '2022-02-01');
        TransactionMatching::factory()->create([
            'matching_rule_id'         => $mr->id,
            'transaction_id'           => $matching->id,
            'reference_transaction_id' => $purchase->id,
        ]);

        // account_credit_lines + credit_line_payments
        $line = AccountCreditLine::factory()->create(['account_id' => $acct7->id]);
        CreditLinePayment::factory()->create(['account_credit_line_id' => $line->id]);

        // fund_reports + account_reports
        $df->createFundReport($fund, 'ALL', '2024-12-31');
        AccountReport::factory()->create(['account_id' => $acct7->id, 'type' => 'ALL', 'as_of' => '2024-12-31']);

        // trade_portfolios (2 active) + items + trade_band_reports. Each
        // TradePortfolio::factory() makes its OWN portfolio, deliberately NOT
        // fund 1's: a controller test PUTs portfolio_id=1 and fails the
        // date-overlap check if that portfolio already carries a trade portfolio.
        // An API index test also needs ≥2 currently-active trade portfolios.
        for ($i = 0; $i < 2; $i++) {
            $tp = TradePortfolio::factory()->create([
                'start_dt' => '2022-01-01',
                'end_dt'   => '9999-12-31',
            ]);
            // Items must reference real assets by (symbol,type): the trade
            // portfolio show/rebalance pages resolve each item via
            // AssetExt::getAsset() and 500 on an unknown symbol.
            TradePortfolioItem::factory()->create(['trade_portfolio_id' => $tp->id, 'symbol' => 'SPY', 'type' => 'STK', 'target_share' => 0.6]);
            TradePortfolioItem::factory()->create(['trade_portfolio_id' => $tp->id, 'symbol' => 'CASH', 'type' => 'CSH', 'target_share' => 0.4]);
        }
        TradeBandReport::factory()->create(['fund_id' => $fund->id, 'as_of' => '2024-12-31']);

        // NOTE: no cash_deposits/deposit_requests — the real baseline had none,
        // and the cashDeposits index view calls an Ext-only method that 500s on a
        // base model (latent bug), so seeding one would break the index + ACL row.

        // 7. QA / Dusk login users (claude@test.local, qa-* roles) on the fund.
        $this->call(QaTestUsersSeeder::class);
    }
}
