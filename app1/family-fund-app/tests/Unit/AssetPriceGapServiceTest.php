<?php

namespace Tests\Unit;

use App\Models\AssetPrice;
use App\Models\ExchangeHoliday;
use App\Services\AssetPriceGapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssetPriceGapServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AssetPriceGapService $gapService;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear existing asset price data to ensure clean test state
        // This is necessary because the service queries ALL asset prices, not filtered by asset
        \DB::table('asset_prices')->delete();

        $this->gapService = new AssetPriceGapService();
    }

    /**
     * Test findGaps returns empty array when all trading days have data
     */
    public function test_findGaps_returns_empty_when_no_gaps(): void
    {
        // Create asset prices for all trading days in the last 7 calendar days
        // Service checks last N calendar days, so we need data for all trading days in that range
        $daysToCheck = 7;
        $startDate = Carbon::now()->subDays($daysToCheck - 1)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $date = $startDate->copy();
        while ($date <= $endDate) {
            if (!$date->isWeekend()) {
                AssetPrice::factory()->create([
                    'start_dt' => $date->format('Y-m-d H:i:s'),
                ]);
            }
            $date->addDay();
        }

        $result = $this->gapService->findGaps($daysToCheck, 'NYSE');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test findGaps detects missing trading days
     */
    public function test_findGaps_detects_missing_trading_days(): void
    {
        Carbon::setTestNow('2026-01-16'); // Friday

        // Create data for Monday-Tuesday-Wednesday (Jan 12-14)
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']); // Tue
        AssetPrice::factory()->create(['start_dt' => '2026-01-14 10:00:00']); // Wed
        // Missing: Thursday 15th, Friday 16th

        $result = $this->gapService->findGaps(7, 'NYSE');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains('2026-01-15', $result); // Thu
        $this->assertContains('2026-01-16', $result); // Fri
    }

    /**
     * Test findGaps excludes weekends
     */
    public function test_findGaps_excludes_weekends(): void
    {
        Carbon::setTestNow('2026-01-19'); // Monday

        // Create data for Mon-Tue-Wed-Thu-Fri last week (Jan 12-16)
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']); // Tue
        AssetPrice::factory()->create(['start_dt' => '2026-01-14 10:00:00']); // Wed
        AssetPrice::factory()->create(['start_dt' => '2026-01-15 10:00:00']); // Thu
        AssetPrice::factory()->create(['start_dt' => '2026-01-16 10:00:00']); // Fri
        // Sat 17th, Sun 18th should be excluded
        // Missing: Mon 19th (today)

        $result = $this->gapService->findGaps(7, 'NYSE');

        $this->assertIsArray($result);
        $this->assertCount(1, $result); // Only Monday 19th missing
        $this->assertContains('2026-01-19', $result);
        $this->assertNotContains('2026-01-17', $result); // Sat excluded
        $this->assertNotContains('2026-01-18', $result); // Sun excluded
    }

    /**
     * Test findGaps excludes holidays
     */
    public function test_findGaps_excludes_holidays(): void
    {
        Carbon::setTestNow('2026-01-16'); // Friday

        // Create holiday for Wednesday Jan 14th
        ExchangeHoliday::factory()->create([
            'exchange_code' => 'NYSE',
            'holiday_date' => '2026-01-14',
            'holiday_name' => 'Test Holiday',
            'is_active' => true,
        ]);

        // Create data for Mon-Tue-Thu-Fri (skipping Wed holiday)
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']); // Tue
        // Wed 14th is holiday - should not be detected as gap
        AssetPrice::factory()->create(['start_dt' => '2026-01-15 10:00:00']); // Thu
        AssetPrice::factory()->create(['start_dt' => '2026-01-16 10:00:00']); // Fri

        $result = $this->gapService->findGaps(7, 'NYSE');

        $this->assertIsArray($result);
        $this->assertEmpty($result); // No gaps - holiday excluded
        $this->assertNotContains('2026-01-14', $result); // Holiday not counted as gap
    }

    /**
     * Test findGaps with different exchange codes
     */
    public function test_findGaps_respects_exchange_code(): void
    {
        Carbon::setTestNow('2026-01-16'); // Friday

        // Create NYSE holiday
        ExchangeHoliday::factory()->create([
            'exchange_code' => 'NYSE',
            'holiday_date' => '2026-01-14',
            'holiday_name' => 'NYSE Holiday',
            'is_active' => true,
        ]);

        // Create NASDAQ holiday (different exchange)
        ExchangeHoliday::factory()->create([
            'exchange_code' => 'NASDAQ',
            'holiday_date' => '2026-01-15',
            'holiday_name' => 'NASDAQ Holiday',
            'is_active' => true,
        ]);

        // Create data for Mon-Tue-Thu-Fri
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']); // Tue
        AssetPrice::factory()->create(['start_dt' => '2026-01-15 10:00:00']); // Thu
        AssetPrice::factory()->create(['start_dt' => '2026-01-16 10:00:00']); // Fri

        // For NYSE: Wed 14 is holiday, Thu 15 has data -> no gaps
        $nyseResult = $this->gapService->findGaps(7, 'NYSE');
        $this->assertEmpty($nyseResult);

        // For NASDAQ: Wed 14 has no data and is NOT a NASDAQ holiday -> gap detected
        $nasdaqResult = $this->gapService->findGaps(7, 'NASDAQ');
        $this->assertContains('2026-01-14', $nasdaqResult);
    }

    /**
     * Test findGaps with custom lookback period
     */
    public function test_findGaps_respects_lookback_period(): void
    {
        Carbon::setTestNow('2026-01-16'); // Friday

        // Create data for 3 days ago (Tue 13th)
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']);

        // With 2-day lookback: Only Thu 15, Fri 16 should be checked
        $result2Days = $this->gapService->findGaps(2, 'NYSE');
        $this->assertCount(2, $result2Days);
        $this->assertContains('2026-01-15', $result2Days);
        $this->assertContains('2026-01-16', $result2Days);
        $this->assertNotContains('2026-01-13', $result2Days); // Outside lookback

        // With 5-day lookback: Mon 12, Tue 13, Wed 14, Thu 15, Fri 16
        $result5Days = $this->gapService->findGaps(5, 'NYSE');
        $this->assertGreaterThanOrEqual(3, count($result5Days)); // At least Mon 12, Thu 15, Fri 16
        $this->assertNotContains('2026-01-13', $result5Days); // Has data
    }

    /**
     * Test findGaps returns sorted dates
     */
    public function test_findGaps_returns_sorted_dates(): void
    {
        Carbon::setTestNow('2026-01-16'); // Friday

        // Create data only for Monday (lots of gaps)
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon

        $result = $this->gapService->findGaps(7, 'NYSE');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verify dates are sorted
        $sortedResult = $result;
        sort($sortedResult);
        $this->assertEquals($sortedResult, $result);
    }

    /**
     * Test findGaps handles inactive holidays correctly
     */
    public function test_findGaps_ignores_inactive_holidays(): void
    {
        Carbon::setTestNow('2026-01-16'); // Friday

        // Create inactive holiday for Wednesday
        ExchangeHoliday::factory()->create([
            'exchange_code' => 'NYSE',
            'holiday_date' => '2026-01-14',
            'holiday_name' => 'Inactive Holiday',
            'is_active' => false, // Inactive
        ]);

        // Create data for Mon-Tue-Thu-Fri (skipping Wed)
        AssetPrice::factory()->create(['start_dt' => '2026-01-12 10:00:00']); // Mon
        AssetPrice::factory()->create(['start_dt' => '2026-01-13 10:00:00']); // Tue
        AssetPrice::factory()->create(['start_dt' => '2026-01-15 10:00:00']); // Thu
        AssetPrice::factory()->create(['start_dt' => '2026-01-16 10:00:00']); // Fri

        $result = $this->gapService->findGaps(7, 'NYSE');

        // Wed should be detected as gap since holiday is inactive
        $this->assertContains('2026-01-14', $result);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }
}
