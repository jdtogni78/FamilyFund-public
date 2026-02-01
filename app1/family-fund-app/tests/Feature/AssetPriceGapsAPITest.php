<?php

namespace Tests\Feature;

use App\Models\AssetPrice;
use App\Models\ExchangeHoliday;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Feature tests for Asset Price Gaps API endpoint
 * Tests GET /api/asset_prices/gaps
 */
class AssetPriceGapsAPITest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear existing asset price data to ensure clean test state
        \DB::table('asset_prices')->delete();

        // Clear existing holidays to ensure clean test state
        // Tests will create their own holidays as needed
        \DB::table('exchange_holidays')->delete();

        Carbon::setTestNow('2026-01-16 10:00:00'); // Friday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset
        parent::tearDown();
    }

    /**
     * Test gaps endpoint returns 200 with valid parameters
     */
    public function test_gaps_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'lookback_days',
            'exchange',
            'missing_count',
            'missing_dates',
        ]);
    }

    /**
     * Test gaps endpoint detects missing trading days
     */
    public function test_gaps_endpoint_detects_missing_days(): void
    {
        // Create data for Mon-Tue-Wed only (Jan 12-14)
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']); // Tue
        AssetPrice::factory()->create(['start_dt' => '2026-01-14 10:00:00']); // Wed
        // Missing: Thu 15, Fri 16

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);
        $response->assertJson([
            'lookback_days' => 7,
            'exchange' => 'NYSE',
            'missing_count' => 2,
        ]);

        $missingDates = $response->json('missing_dates');
        $this->assertContains('2026-01-15', $missingDates);
        $this->assertContains('2026-01-16', $missingDates);
    }

    /**
     * Test gaps endpoint excludes weekends
     */
    public function test_gaps_endpoint_excludes_weekends(): void
    {
        // Create full week of data (Mon-Fri)
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']); // Tue
        AssetPrice::factory()->create(['start_dt' => '2026-01-14 10:00:00']); // Wed
        AssetPrice::factory()->create(['start_dt' => '2026-01-15 10:00:00']); // Thu
        AssetPrice::factory()->create(['start_dt' => '2026-01-16 10:00:00']); // Fri
        // Sat 17, Sun 18 should not be flagged as gaps

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);
        $response->assertJson([
            'missing_count' => 0,
        ]);

        $missingDates = $response->json('missing_dates');
        $this->assertNotContains('2026-01-17', $missingDates); // Saturday
        $this->assertNotContains('2026-01-18', $missingDates); // Sunday
    }

    /**
     * Test gaps endpoint excludes holidays
     */
    public function test_gaps_endpoint_excludes_holidays(): void
    {
        // Create NYSE holiday for Wednesday
        ExchangeHoliday::factory()->create([
            'exchange_code' => 'NYSE',
            'holiday_date' => '2026-01-14',
            'holiday_name' => 'Test Holiday',
            'is_active' => true,
        ]);

        // Create data for Mon-Tue-Thu-Fri (skipping Wed holiday)
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']); // Tue
        // Wed 14 is holiday
        AssetPrice::factory()->create(['start_dt' => '2026-01-15 10:00:00']); // Thu
        AssetPrice::factory()->create(['start_dt' => '2026-01-16 10:00:00']); // Fri

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);
        $response->assertJson([
            'missing_count' => 0,
        ]);

        $missingDates = $response->json('missing_dates');
        $this->assertNotContains('2026-01-14', $missingDates); // Holiday excluded
    }

    /**
     * Test gaps endpoint respects days parameter
     */
    public function test_gaps_endpoint_respects_days_parameter(): void
    {
        // Create data for 10 days ago
        AssetPrice::factory()->create([
            'start_dt' => Carbon::now()->subDays(10)->format('Y-m-d H:i:s')
        ]);

        // With days=5, should not include 10-day-old gap
        $response = $this->getJson('/api/asset_prices/gaps?days=5&exchange=NYSE');
        $response->assertStatus(200);

        $missingDates = $response->json('missing_dates');
        $tenDaysAgo = Carbon::now()->subDays(10)->format('Y-m-d');
        $this->assertNotContains($tenDaysAgo, $missingDates);
    }

    /**
     * Test gaps endpoint respects exchange parameter
     */
    public function test_gaps_endpoint_respects_exchange_parameter(): void
    {
        // Create NYSE-specific holiday
        ExchangeHoliday::factory()->create([
            'exchange_code' => 'NYSE',
            'holiday_date' => '2026-01-14',
            'holiday_name' => 'NYSE Holiday',
            'is_active' => true,
        ]);

        // Create data for Mon-Tue-Thu-Fri
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']);
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']);
        AssetPrice::factory()->create(['start_dt' => '2026-01-15 10:00:00']);
        AssetPrice::factory()->create(['start_dt' => '2026-01-16 10:00:00']);

        // For NYSE: Wed 14 is holiday, no gaps
        $nyseResponse = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');
        $nyseResponse->assertJson(['missing_count' => 0]);

        // For NASDAQ: Wed 14 is NOT a NASDAQ holiday, gap detected
        $nasdaqResponse = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NASDAQ');
        $nasdaqMissing = $nasdaqResponse->json('missing_dates');
        $this->assertContains('2026-01-14', $nasdaqMissing);
    }

    /**
     * Test gaps endpoint uses default values
     */
    public function test_gaps_endpoint_uses_defaults(): void
    {
        $response = $this->getJson('/api/asset_prices/gaps');

        $response->assertStatus(200);
        $response->assertJson([
            'lookback_days' => 30, // Default
            'exchange' => 'NYSE', // Default
        ]);
    }

    /**
     * Test gaps endpoint validates days parameter (too small)
     */
    public function test_gaps_endpoint_validates_days_too_small(): void
    {
        $response = $this->getJson('/api/asset_prices/gaps?days=0');

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'days parameter must be between 1 and 365',
        ]);
    }

    /**
     * Test gaps endpoint validates days parameter (too large)
     */
    public function test_gaps_endpoint_validates_days_too_large(): void
    {
        $response = $this->getJson('/api/asset_prices/gaps?days=500');

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'days parameter must be between 1 and 365',
        ]);
    }

    /**
     * Test gaps endpoint validates days parameter (not numeric)
     */
    public function test_gaps_endpoint_validates_days_not_numeric(): void
    {
        $response = $this->getJson('/api/asset_prices/gaps?days=abc');

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'days parameter must be between 1 and 365',
        ]);
    }

    /**
     * Test gaps endpoint returns empty array when no gaps
     */
    public function test_gaps_endpoint_returns_empty_when_no_gaps(): void
    {
        // Create data for last 7 trading days including today
        $date = Carbon::now()->startOfDay();
        $daysCreated = 0;

        while ($daysCreated < 7) {
            if (!$date->isWeekend()) {
                AssetPrice::factory()->create([
                    'start_dt' => $date->format('Y-m-d H:i:s'),
                ]);
                $daysCreated++;
            }
            $date = $date->subDay();
        }

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);
        $response->assertJson([
            'missing_count' => 0,
            'missing_dates' => [],
        ]);
    }

    /**
     * Test gaps endpoint returns sorted dates
     */
    public function test_gaps_endpoint_returns_sorted_dates(): void
    {
        // Create data only for Monday (lots of gaps)
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);

        $missingDates = $response->json('missing_dates');
        $sortedDates = $missingDates;
        sort($sortedDates);

        $this->assertEquals($sortedDates, $missingDates);
    }

    /**
     * Test gaps endpoint with real-world scenario
     */
    public function test_gaps_endpoint_real_world_scenario(): void
    {
        Carbon::setTestNow('2026-01-20 16:00:00'); // Tuesday after market close

        // Scenario: Trading system was down Thu-Fri last week
        // Create data for Mon-Wed (Jan 12-14)
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']); // Tue
        AssetPrice::factory()->create(['start_dt' => '2026-01-14 10:00:00']); // Wed
        // Missing: Thu 15, Fri 16
        // Weekend: Sat 17, Sun 18 (should be excluded)
        // Create data for Mon-Tue this week (Jan 19-20)
        AssetPrice::factory()->create(['start_dt' => '2026-01-19 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-20 10:00:00']); // Tue

        $response = $this->getJson('/api/asset_prices/gaps?days=10&exchange=NYSE');

        $response->assertStatus(200);
        $response->assertJson([
            'missing_count' => 2,
        ]);

        $missingDates = $response->json('missing_dates');
        $this->assertContains('2026-01-15', $missingDates); // Thu
        $this->assertContains('2026-01-16', $missingDates); // Fri
        $this->assertNotContains('2026-01-17', $missingDates); // Sat (weekend)
        $this->assertNotContains('2026-01-18', $missingDates); // Sun (weekend)
    }

    /**
     * Test gaps endpoint detects days without new data (single record spanning multiple days)
     */
    public function test_gaps_endpoint_detects_days_without_new_data(): void
    {
        // Create a single record spanning Mon-Fri (no new data for Tue-Fri)
        AssetPrice::factory()->create([
            'start_dt' => '2026-01-12 10:00:00', // Mon
            'end_dt' => '2026-01-16 23:59:59',   // Fri
        ]);

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);

        $missingDates = $response->json('missing_dates');

        // Should detect Tue-Fri as missing (no NEW data on those days)
        $this->assertContains('2026-01-13', $missingDates); // Tue
        $this->assertContains('2026-01-14', $missingDates); // Wed
        $this->assertContains('2026-01-15', $missingDates); // Thu
        $this->assertContains('2026-01-16', $missingDates); // Fri

        $this->assertEquals(4, $response->json('missing_count'));
    }

    /**
     * Test gaps endpoint with mix of daily updates and long spans
     */
    public function test_gaps_endpoint_mix_of_daily_and_long_spans(): void
    {
        // Normal daily updates Mon-Wed
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']); // Tue
        AssetPrice::factory()->create(['start_dt' => '2026-01-14 10:00:00']); // Wed

        // Single record spanning Thu-Fri (no new data on Fri)
        AssetPrice::factory()->create([
            'start_dt' => '2026-01-15 10:00:00', // Thu
            'end_dt' => '2026-01-16 23:59:59',   // Fri
        ]);

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);

        $missingDates = $response->json('missing_dates');

        // Should only detect Fri as missing (Thu has new data)
        $this->assertNotContains('2026-01-12', $missingDates); // Mon has data
        $this->assertNotContains('2026-01-13', $missingDates); // Tue has data
        $this->assertNotContains('2026-01-14', $missingDates); // Wed has data
        $this->assertNotContains('2026-01-15', $missingDates); // Thu has NEW data
        $this->assertContains('2026-01-16', $missingDates);    // Fri no NEW data

        $this->assertEquals(1, $response->json('missing_count'));
    }

    /**
     * Test gaps endpoint with record spanning weekend
     */
    public function test_gaps_endpoint_long_span_over_weekend(): void
    {
        // Set test "now" to ensure consistent date range
        // Use Jan 27 (Tue) to avoid MLK Day (Jan 19) holiday
        Carbon::setTestNow('2026-01-27 10:00:00'); // Tue

        // Create records for non-spanning days (Wed 1/21, Tue 1/27)
        foreach (['2026-01-21', '2026-01-27'] as $date) {
            AssetPrice::factory()->create([
                'start_dt' => "$date 10:00:00",
                'end_dt' => "$date 23:59:59",
            ]);
        }

        // Create record from Thu to Mon spanning weekend
        // Thu Jan 22 -> Mon Jan 26
        AssetPrice::factory()->create([
            'start_dt' => '2026-01-22 10:00:00', // Thu
            'end_dt' => '2026-01-26 23:59:59',   // Mon
        ]);

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);

        $missingDates = $response->json('missing_dates');

        // Should detect Fri and Mon as missing (no NEW data)
        // Weekend should be excluded
        $this->assertContains('2026-01-23', $missingDates);    // Fri
        $this->assertNotContains('2026-01-24', $missingDates); // Sat (weekend)
        $this->assertNotContains('2026-01-25', $missingDates); // Sun (weekend)
        $this->assertContains('2026-01-26', $missingDates);    // Mon

        $this->assertEquals(2, $response->json('missing_count'));

        Carbon::setTestNow(); // Clear test time
    }

    /**
     * Test gaps endpoint with record spanning holiday
     */
    public function test_gaps_endpoint_long_span_over_holiday(): void
    {
        // Set test "now" to ensure consistent date range
        Carbon::setTestNow('2026-01-16 10:00:00'); // Fri

        // Create NYSE holiday for Wednesday
        ExchangeHoliday::factory()->create([
            'exchange_code' => 'NYSE',
            'holiday_date' => '2026-01-14',
            'holiday_name' => 'Test Holiday',
            'is_active' => true,
        ]);

        // Create records for non-spanning days (Mon 1/12, Fri 1/16)
        foreach (['2026-01-12', '2026-01-16'] as $date) {
            AssetPrice::factory()->create([
                'start_dt' => "$date 10:00:00",
                'end_dt' => "$date 23:59:59",
            ]);
        }

        // Create record from Tue to Thu spanning holiday
        AssetPrice::factory()->create([
            'start_dt' => '2026-01-13 10:00:00', // Tue
            'end_dt' => '2026-01-15 23:59:59',   // Thu
        ]);

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);

        $missingDates = $response->json('missing_dates');

        // Should detect Thu as missing (no NEW data)
        // Holiday Wed should be excluded
        $this->assertNotContains('2026-01-14', $missingDates); // Wed (holiday)
        $this->assertContains('2026-01-15', $missingDates);    // Thu

        // Only 1 missing trading day
        $this->assertEquals(1, $response->json('missing_count'));

        Carbon::setTestNow(); // Clear test time
    }

    /**
     * Test gaps endpoint detects very long span (2 weeks)
     */
    public function test_gaps_endpoint_detects_very_long_span(): void
    {
        // Create single record spanning 2 weeks
        AssetPrice::factory()->create([
            'start_dt' => '2026-01-05 10:00:00', // Mon
            'end_dt' => '2026-01-16 23:59:59',   // Fri 2 weeks later
        ]);

        $response = $this->getJson('/api/asset_prices/gaps?days=15&exchange=NYSE');

        $response->assertStatus(200);

        $missingDates = $response->json('missing_dates');

        // Should detect all trading days except Jan 5 (the start date)
        // 2 weeks = 10 trading days, minus 1 for start date = 9 missing
        $this->assertGreaterThanOrEqual(9, $response->json('missing_count'));

        // Verify start date is NOT missing
        $this->assertNotContains('2026-01-05', $missingDates);

        // Verify some end dates ARE missing
        $this->assertContains('2026-01-15', $missingDates); // Thu
        $this->assertContains('2026-01-16', $missingDates); // Fri
    }

    /**
     * Test real-world TECL gap scenario from prod (Jan 2026).
     *
     * Calendar for Jan 2026:
     *   Jan 9  (Fri) - has data (start_dt)
     *   Jan 10 (Sat) - weekend
     *   Jan 11 (Sun) - weekend
     *   Jan 12 (Mon) - trading day, MISSING
     *   Jan 13 (Tue) - trading day, MISSING
     *   Jan 14 (Wed) - has data (new record starts)
     *
     * Expected: 2 trading days without new data (Jan 12, Jan 13)
     */
    public function test_gaps_endpoint_tecl_real_world_scenario(): void
    {
        Carbon::setTestNow('2026-01-14 16:00:00'); // Wed after market close

        // Simulate price record spanning Fri Jan 9 to Wed Jan 14
        // This means no NEW data on Mon Jan 12 or Tue Jan 13
        AssetPrice::factory()->create([
            'start_dt' => '2026-01-09 10:00:00', // Fri
            'end_dt' => '2026-01-14 23:59:59',   // Wed (spans to current day)
        ]);

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);

        $missingDates = $response->json('missing_dates');

        // Should detect Mon Jan 12 and Tue Jan 13 as missing
        $this->assertContains('2026-01-12', $missingDates); // Mon - no new data
        $this->assertContains('2026-01-13', $missingDates); // Tue - no new data

        // Weekend should be excluded
        $this->assertNotContains('2026-01-10', $missingDates); // Sat
        $this->assertNotContains('2026-01-11', $missingDates); // Sun

        // Start date should NOT be missing (has new data)
        $this->assertNotContains('2026-01-09', $missingDates); // Fri has data

        // Verify count (should be exactly 2 for the 7-day lookback with this data)
        $this->assertGreaterThanOrEqual(2, $response->json('missing_count'));
    }

    /**
     * Test that holidays are correctly excluded when detecting gaps.
     *
     * This was the root cause of the TECL gap issue in prod:
     * without holidays loaded, Christmas and New Year's were flagged as gaps.
     */
    public function test_gaps_endpoint_excludes_real_holidays(): void
    {
        Carbon::setTestNow('2026-01-05 10:00:00'); // Mon

        // Add real 2026 holidays (use updateOrCreate to avoid duplicate key errors)
        ExchangeHoliday::updateOrCreate(
            ['exchange_code' => 'NYSE', 'holiday_date' => '2026-01-01'],
            ['holiday_name' => "New Year's Day", 'is_active' => true, 'source' => 'test']
        );

        // Create data for Dec 31 (Wed) and Jan 2 (Fri) and Jan 5 (Mon)
        AssetPrice::factory()->create(['start_dt' => '2025-12-31 10:00:00']); // Wed
        AssetPrice::factory()->create(['start_dt' => '2026-01-02 10:00:00']); // Fri
        AssetPrice::factory()->create(['start_dt' => '2026-01-05 10:00:00']); // Mon

        $response = $this->getJson('/api/asset_prices/gaps?days=7&exchange=NYSE');

        $response->assertStatus(200);

        $missingDates = $response->json('missing_dates');

        // Jan 1 is New Year's Day - should NOT be flagged as missing
        $this->assertNotContains('2026-01-01', $missingDates);

        // Weekend should be excluded
        $this->assertNotContains('2026-01-03', $missingDates); // Sat
        $this->assertNotContains('2026-01-04', $missingDates); // Sun
    }
}

