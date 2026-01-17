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
}
