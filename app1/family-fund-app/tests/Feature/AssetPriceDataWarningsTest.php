<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetPrice;
use App\Models\ExchangeHoliday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for asset price data warnings (gaps, overlaps, days without new data)
 * Tests the DetectsDataIssuesTrait functionality via AssetPriceControllerExt
 */
class AssetPriceDataWarningsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->asset = Asset::factory()->create(['name' => 'TEST']);

        Carbon::setTestNow('2026-01-20 16:00:00'); // Tuesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Test detection of records spanning multiple trading days (days without new data)
     */
    public function test_detects_days_without_new_data_spanning_multiple_trading_days()
    {
        // Create a record spanning Mon-Fri (record starts Mon, ends Fri)
        // Trading days between start and end: Tue, Wed, Thu = 3 days without new data
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-12', // Monday
            'end_dt' => '2026-01-16',   // Friday
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');

        $this->assertNotEmpty($dataWarnings['longSpans']);
        $this->assertCount(1, $dataWarnings['longSpans']);

        $longSpan = $dataWarnings['longSpans'][0];
        $this->assertEquals('TEST', $longSpan['name']);
        $this->assertEquals('2026-01-12', $longSpan['from']);
        $this->assertEquals('2026-01-16', $longSpan['to']);
        $this->assertEquals(3, $longSpan['days']); // 3 trading days between (Tue, Wed, Thu)
        $this->assertEquals(4, $longSpan['calendar_days']);
    }

    /**
     * Test that single-day records are NOT flagged
     */
    public function test_single_day_records_not_flagged()
    {
        // Create multiple single-day records
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-12',
            'end_dt' => '2026-01-13',
        ]);

        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 101.00,
            'start_dt' => '2026-01-13',
            'end_dt' => '2026-01-14',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');
        $this->assertEmpty($dataWarnings['longSpans']);
    }

    /**
     * Test that records spanning weekends are correctly calculated
     */
    public function test_records_spanning_weekend_calculated_correctly()
    {
        // Create a record from Friday to Monday (spans weekend)
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-16', // Friday
            'end_dt' => '2026-01-19',   // Monday (4 calendar days, 1 trading day)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');

        // Should NOT be flagged because it only spans 1 trading day (Monday)
        $this->assertEmpty($dataWarnings['longSpans']);
    }

    /**
     * Test that records spanning holidays are correctly calculated
     */
    public function test_records_spanning_holidays_calculated_correctly()
    {
        // Create NYSE holiday for Wednesday
        ExchangeHoliday::factory()->create([
            'exchange_code' => 'NYSE',
            'holiday_date' => '2026-01-14',
            'holiday_name' => 'Test Holiday',
            'is_active' => true,
        ]);

        // Create a record from Tue to Thu (spans holiday)
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-13', // Tuesday
            'end_dt' => '2026-01-15',   // Thursday (3 calendar days, 1 trading day because Wed is holiday)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');

        // Should NOT be flagged because it only spans 1 trading day (Thursday, since Wed is holiday)
        $this->assertEmpty($dataWarnings['longSpans']);
    }

    /**
     * Test multiple records with varying span lengths
     */
    public function test_detects_multiple_records_with_long_spans()
    {
        // Good: single day record
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-05',
            'end_dt' => '2026-01-06',
        ]);

        // Bad: spans Mon-Wed (Tue in between = 1 trading day, but need >1 to flag)
        // Let's make it span more days
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 101.00,
            'start_dt' => '2026-01-06', // Monday
            'end_dt' => '2026-01-09',   // Thursday (Tue, Wed in between = 2 trading days)
        ]);

        // Good: single day
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 102.00,
            'start_dt' => '2026-01-09',
            'end_dt' => '2026-01-12',
        ]);

        // Bad: Long span
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 103.00,
            'start_dt' => '2026-01-12', // Monday
            'end_dt' => '2026-01-23',   // Friday next week (9 trading days in between)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');

        $this->assertCount(2, $dataWarnings['longSpans']);

        // Verify both records are flagged (sorted descending by date, newest first)
        $this->assertEquals('2026-01-12', $dataWarnings['longSpans'][0]['from']);
        $this->assertEquals('2026-01-06', $dataWarnings['longSpans'][1]['from']);
    }

    /**
     * Test that current (9999) end dates are ignored
     */
    public function test_current_records_with_9999_end_date_not_flagged()
    {
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-01',
            'end_dt' => '9999-12-31', // Current record
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');
        $this->assertEmpty($dataWarnings['longSpans']);
    }

    /**
     * Test combination of overlaps, gaps, and long spans
     */
    public function test_detects_combination_of_issues()
    {
        $asset2 = Asset::factory()->create(['name' => 'TEST2']);

        // Asset 1: Has long span
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-05', // Mon
            'end_dt' => '2026-01-07',   // Wed (Tue in between = 1 trading day, need >1)
        ]);

        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 101.00,
            'start_dt' => '2026-01-07', // Wed
            'end_dt' => '2026-01-09',   // Fri (Thu in between = 1 trading day, need >1)
        ]);

        // Asset 1: Has gap (missing Mon-Tue = 2 trading days)
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 102.00,
            'start_dt' => '2026-01-14', // Wed (gap from Fri Jan 9: Mon+Tue = 2 trading days)
            'end_dt' => '2026-01-15',
        ]);

        // Asset 2: Has overlap
        AssetPrice::factory()->create([
            'asset_id' => $asset2->id,
            'price' => 200.00,
            'start_dt' => '2026-01-05',
            'end_dt' => '2026-01-08',
        ]);

        AssetPrice::factory()->create([
            'asset_id' => $asset2->id,
            'price' => 201.00,
            'start_dt' => '2026-01-07', // Overlaps with previous!
            'end_dt' => '2026-01-10',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index'));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');

        // Should detect all three types of issues
        $this->assertNotEmpty($dataWarnings['longSpans'], 'Expected to find long spans');
        $this->assertNotEmpty($dataWarnings['gaps'], 'Expected to find gaps');
        $this->assertNotEmpty($dataWarnings['overlaps'], 'Expected to find overlaps');
    }

    /**
     * Test that warnings appear in view
     */
    public function test_warnings_appear_in_view()
    {
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-12',
            'end_dt' => '2026-01-19', // 5 trading days
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);
        $response->assertSee('Days Without New Data:');
        $response->assertSee('TEST');
        $response->assertSee('trading day');
        $response->assertSee('2026-01-12');
        $response->assertSee('2026-01-19');
    }

    /**
     * Test that table rows are highlighted for long spans
     */
    public function test_table_rows_highlighted_for_long_spans()
    {
        $longSpanPrice = AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-12',
            'end_dt' => '2026-01-16', // 4 trading days
        ]);

        $normalPrice = AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 101.00,
            'start_dt' => '2026-01-16',
            'end_dt' => '2026-01-17', // 1 day
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');

        // Long span record should be in the IDs
        $this->assertContains($longSpanPrice->id, $dataWarnings['longSpanIds']);
        $this->assertNotContains($normalPrice->id, $dataWarnings['longSpanIds']);
    }

    /**
     * Test real-world scenario: Daily price updates stopped
     */
    public function test_real_world_scenario_price_updates_stopped()
    {
        // Scenario: Daily price updates were working fine, then stopped for 2 weeks

        // Normal daily updates (Jan 5-9)
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 105.00,
            'start_dt' => '2026-01-05',
            'end_dt' => '2026-01-06',
        ]);
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 106.00,
            'start_dt' => '2026-01-06',
            'end_dt' => '2026-01-07',
        ]);
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 107.00,
            'start_dt' => '2026-01-07',
            'end_dt' => '2026-01-08',
        ]);
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 108.00,
            'start_dt' => '2026-01-08',
            'end_dt' => '2026-01-09',
        ]);

        // Gap: System down, single record spanning 2+ weeks (many trading days)
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 110.00,
            'start_dt' => '2026-01-09', // Thu
            'end_dt' => '2026-01-26',   // Mon 2+ weeks later
        ]);

        // Normal daily updates resumed (Jan 26-27)
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 111.00,
            'start_dt' => '2026-01-26',
            'end_dt' => '2026-01-27',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');

        // Should detect the 2-week span
        $this->assertNotEmpty($dataWarnings['longSpans']);
        $this->assertGreaterThanOrEqual(1, count($dataWarnings['longSpans']));

        // Find the long span
        $foundLongSpan = false;
        foreach ($dataWarnings['longSpans'] as $span) {
            if ($span['from'] === '2026-01-09' && $span['to'] === '2026-01-26') {
                $foundLongSpan = true;
                $this->assertGreaterThanOrEqual(10, $span['days']); // At least 10 trading days
                break;
            }
        }
        $this->assertTrue($foundLongSpan, 'Expected to find long span from 2026-01-09 to 2026-01-26');
    }

    /**
     * Test edge case: Record ending exactly on weekend
     */
    public function test_record_ending_on_saturday()
    {
        // Record from Wed to Sat (3 calendar days, 2 trading days: Thu, Fri)
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-14', // Wednesday
            'end_dt' => '2026-01-17',   // Saturday
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', ['asset_id' => $this->asset->id]));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');

        // Should detect 2 trading days (Thu, Fri)
        $this->assertCount(1, $dataWarnings['longSpans']);
        $this->assertEquals(2, $dataWarnings['longSpans'][0]['days']);
    }

    /**
     * Test with multiple assets
     */
    public function test_detects_long_spans_across_multiple_assets()
    {
        $asset2 = Asset::factory()->create(['name' => 'TEST2']);
        $asset3 = Asset::factory()->create(['name' => 'TEST3']);

        // Asset 1: Has long span
        AssetPrice::factory()->create([
            'asset_id' => $this->asset->id,
            'price' => 100.00,
            'start_dt' => '2026-01-12',
            'end_dt' => '2026-01-16',
        ]);

        // Asset 2: Good (single day)
        AssetPrice::factory()->create([
            'asset_id' => $asset2->id,
            'price' => 200.00,
            'start_dt' => '2026-01-12',
            'end_dt' => '2026-01-13',
        ]);

        // Asset 3: Has long span
        AssetPrice::factory()->create([
            'asset_id' => $asset3->id,
            'price' => 300.00,
            'start_dt' => '2026-01-12',
            'end_dt' => '2026-01-20', // 6 trading days
        ]);

        // Filter to only these 3 assets
        $response = $this->actingAs($this->user)
            ->get(route('assetPrices.index', [
                'asset_id' => [$this->asset->id, $asset2->id, $asset3->id]
            ]));

        $response->assertStatus(200);

        $dataWarnings = $response->viewData('dataWarnings');

        // Should detect 2 long spans (asset 1 and 3)
        $this->assertGreaterThanOrEqual(2, count($dataWarnings['longSpans']));

        // Check the assets with long spans
        $assetNames = array_column($dataWarnings['longSpans'], 'name');
        $this->assertContains('TEST', $assetNames);
        $this->assertContains('TEST3', $assetNames);
        $this->assertNotContains('TEST2', $assetNames);
    }
}
