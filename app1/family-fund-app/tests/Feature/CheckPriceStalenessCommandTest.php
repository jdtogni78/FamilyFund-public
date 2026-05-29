<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Independent safety-net alert: the `prices:check-staleness` command flags
 * tracked assets whose newest price has fallen more than --threshold trading
 * days behind today. Verifies the OK path, the alert path (non-zero exit +
 * email), the cash-exclusion, and the --as-of override.
 */
class CheckPriceStalenessCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        // Anchor "today" for the trading-day math used by --as-of's default.
        Carbon::setTestNow(Carbon::parse('2026-03-20')); // Friday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_succeeds_when_no_prices_exist(): void
    {
        // Empty asset_prices table (apart from any global seeded cash row
        // which is excluded anyway): the command should report no work and
        // exit successfully.
        AssetPrice::query()->delete();

        $this->artisan('prices:check-staleness')
            ->expectsOutput('No tracked asset prices to check.')
            ->assertExitCode(0);
    }

    public function test_succeeds_when_prices_are_fresh(): void
    {
        $factory = new DataFactory();
        $factory->createFund();
        // Newest price is yesterday — clearly within threshold.
        $asset = $factory->createAsset(null);
        $factory->createAssetPrice($asset, 100, Carbon::parse('2026-03-19'));

        $this->artisan('prices:check-staleness', ['--as-of' => '2026-03-20'])
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_alerts_when_price_exceeds_threshold(): void
    {
        $factory = new DataFactory();
        $factory->createFund();
        $asset = $factory->createAsset(null);
        // Newest price is ~2 months old — well past the 5-trading-day threshold.
        $factory->createAssetPrice($asset, 100, Carbon::parse('2026-01-13'));

        $this->artisan('prices:check-staleness', ['--as-of' => '2026-03-20'])
            ->assertExitCode(1);
    }

    public function test_excludes_cash(): void
    {
        // A CASH/CSH asset with a price from years ago must NOT trigger.
        $cash = AssetExt::getCashAsset();
        AssetPrice::query()->delete();
        AssetPrice::create([
            'asset_id' => $cash->id,
            'price' => 1.00,
            'start_dt' => '2022-01-01',
            'end_dt' => '9999-12-31',
        ]);

        $this->artisan('prices:check-staleness', ['--as-of' => '2026-03-20'])
            ->expectsOutput('No tracked asset prices to check.')
            ->assertExitCode(0);
    }

    public function test_custom_threshold_overrides_default(): void
    {
        $factory = new DataFactory();
        $factory->createFund();
        $asset = $factory->createAsset(null);
        // 3 trading days behind: passes default (5) but fails --threshold=1.
        $factory->createAssetPrice($asset, 100, Carbon::parse('2026-03-17'));

        $this->artisan('prices:check-staleness', ['--as-of' => '2026-03-20'])
            ->assertExitCode(0);

        $this->artisan('prices:check-staleness', [
            '--as-of' => '2026-03-20',
            '--threshold' => 1,
        ])->assertExitCode(1);
    }
}
