<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use Tests\DataFactory;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class AssetPriceOverlapTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    protected DataFactory $df;
    protected string $source;
    protected string $dataSource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->df = new DataFactory();
        $this->df->createFund();
        $this->source = $this->df->portfolio->source;
        $this->dataSource = AssetExt::getDataSourceForPortfolio($this->source);
    }

    /**
     * Test that bulk price updates don't create overlapping date ranges
     * This reproduces the gap backfill scenario where:
     * 1. Daily report creates a wide range (2025-12-30 to 2026-01-17)
     * 2. Gap backfill tries to create smaller ranges (2025-12-31 to 2026-01-02)
     * Expected: No overlapping ranges should exist
     */
    #[Test]
    public function test_bulk_update_prevents_overlapping_ranges()
    {
        // Enable query logging to debug
        DB::enableQueryLog();

        // Step 1: Create initial wide-range price record (simulating daily report)
        $response1 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 10:00:00',
            'symbols' => [
                [
                    'name' => 'TEST_SOXL',
                    'type' => 'STK',
                    'price' => '60.50',
                ],
            ],
        ]);

        $response1->assertStatus(200);

        // Get the created asset (now keyed by data_source, not source)
        $asset = AssetExt::where('name', 'TEST_SOXL')
            ->where('data_source', $this->dataSource)
            ->firstOrFail();

        // Verify initial record
        $prices = AssetPrice::where('asset_id', $asset->id)->get();
        $this->assertCount(1, $prices);
        $this->assertEquals('2025-12-30', $prices[0]->start_dt->format('Y-m-d'));
        $this->assertEquals('9999-12-31', $prices[0]->end_dt->format('Y-m-d'));

        // Step 2: Update with same price on a later date (simulating no price change)
        // This creates a wide range from 2025-12-30 to present
        $response2 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-17 10:00:00',
            'symbols' => [
                [
                    'name' => 'TEST_SOXL',
                    'type' => 'STK',
                    'price' => '60.50', // Same price
                ],
            ],
        ]);

        $response2->assertStatus(200);

        // The existing record should extend forward, no new record created
        $prices = AssetPrice::where('asset_id', $asset->id)->get();
        $this->assertCount(1, $prices);
        $this->assertEquals('2025-12-30', $prices[0]->start_dt->format('Y-m-d'));
        $this->assertEquals('9999-12-31', $prices[0]->end_dt->format('Y-m-d'));

        // Step 3: Gap backfill tries to fill intermediate date (2025-12-31)
        $response3 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-31 10:00:00',
            'symbols' => [
                [
                    'name' => 'TEST_SOXL',
                    'type' => 'STK',
                    'price' => '60.50', // Same price
                ],
            ],
        ]);

        $response3->assertStatus(200);

        // Should still be one record, extended backwards
        $prices = AssetPrice::where('asset_id', $asset->id)->get();
        $this->assertCount(1, $prices);

        // Step 4: Gap backfill fills another intermediate date (2026-01-02)
        $response4 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-02 10:00:00',
            'symbols' => [
                [
                    'name' => 'TEST_SOXL',
                    'type' => 'STK',
                    'price' => '60.50', // Same price
                ],
            ],
        ]);

        $response4->assertStatus(200);

        // CRITICAL: Verify NO overlapping ranges exist
        $overlaps = $this->getOverlappingRanges($asset->id);

        // Debug: Print all queries and records
        if (count($overlaps) > 0) {
            dump("=== ALL QUERIES ===");
            dump(DB::getQueryLog());
            dump("=== ALL ASSET PRICE RECORDS ===");
            dump(AssetPrice::where('asset_id', $asset->id)->orderBy('start_dt')->get()->toArray());
        }

        $this->assertCount(0, $overlaps,
            "Found overlapping price ranges:\n" . json_encode($overlaps, JSON_PRETTY_PRINT));

        // Verify we still have a clean set of records
        $prices = AssetPrice::where('asset_id', $asset->id)
            ->orderBy('start_dt')
            ->get();

        // All records should have non-overlapping date ranges
        for ($i = 0; $i < $prices->count() - 1; $i++) {
            $current = $prices[$i];
            $next = $prices[$i + 1];

            $this->assertLessThanOrEqual(
                $next->start_dt,
                $current->end_dt,
                "Gap found between records: {$current->end_dt} and {$next->start_dt}"
            );

            $this->assertNotEquals(
                $next->start_dt->format('Y-m-d'),
                $current->end_dt->format('Y-m-d'),
                "Overlap found: record ends {$current->end_dt}, next starts {$next->start_dt}"
            );
        }
    }

    /**
     * Test that price changes properly split date ranges without overlap
     */
    #[Test]
    public function test_price_change_creates_non_overlapping_ranges()
    {
        // Create initial price
        $response1 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 10:00:00',
            'symbols' => [
                ['name' => 'TEST_TECL', 'type' => 'STK', 'price' => '50.00'],
            ],
        ]);

        $response1->assertStatus(200);

        $asset = AssetExt::where('name', 'TEST_TECL')->where('data_source', $this->dataSource)->firstOrFail();

        // Update with different price
        $response2 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-10 10:00:00',
            'symbols' => [
                ['name' => 'TEST_TECL', 'type' => 'STK', 'price' => '55.00'], // Price changed
            ],
        ]);

        $response2->assertStatus(200);

        // Should have 2 records now
        $prices = AssetPrice::where('asset_id', $asset->id)->orderBy('start_dt')->get();
        $this->assertCount(2, $prices);

        // Verify no overlaps
        $overlaps = $this->getOverlappingRanges($asset->id);
        $this->assertCount(0, $overlaps,
            "Found overlapping price ranges after price change:\n" . json_encode($overlaps, JSON_PRETTY_PRINT));

        // Verify ranges are contiguous
        $this->assertEquals('2025-12-30', $prices[0]->start_dt->format('Y-m-d'));
        $this->assertEquals('2026-01-10', $prices[0]->end_dt->format('Y-m-d'));
        $this->assertEquals('50.00', $prices[0]->price);

        $this->assertEquals('2026-01-10', $prices[1]->start_dt->format('Y-m-d'));
        $this->assertEquals('9999-12-31', $prices[1]->end_dt->format('Y-m-d'));
        $this->assertEquals('55.00', $prices[1]->price);
    }

    /**
     * Test gap backfill with fluctuating prices to stress-test the logic
     */
    #[Test]
    public function test_gap_backfill_with_price_fluctuations()
    {
        // Simulate a more complex scenario:
        // Day 1: price 60.50
        // (gap - days 2,3,4,5)
        // Day 6: price 60.50 (same)
        // Day 7: price 61.00 (changed)
        // Now backfill the gaps with historical prices that may differ

        // Day 1: Initial price
        $response1 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 10:00:00',
            'symbols' => [['name' => 'UPRO', 'type' => 'STK', 'price' => '60.50']],
        ]);
        $response1->assertStatus(200);

        $asset = AssetExt::where('name', 'UPRO')->where('data_source', $this->dataSource)->firstOrFail();

        // Day 6: Same price (no gap detection yet, simulating daily run)
        $response2 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-04 10:00:00',
            'symbols' => [['name' => 'UPRO', 'type' => 'STK', 'price' => '60.50']],
        ]);
        $response2->assertStatus(200);

        // Day 7: Price changed
        $response3 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-05 10:00:00',
            'symbols' => [['name' => 'UPRO', 'type' => 'STK', 'price' => '61.00']],
        ]);
        $response3->assertStatus(200);

        // Now backfill gaps: Day 2 with historical price (might be slightly different!)
        $response4 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-31 10:00:00',
            'symbols' => [['name' => 'UPRO', 'type' => 'STK', 'price' => '60.50']],
        ]);
        $response4->assertStatus(200);

        // Backfill Day 3
        $response5 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-01 10:00:00',
            'symbols' => [['name' => 'UPRO', 'type' => 'STK', 'price' => '60.50']],
        ]);
        $response5->assertStatus(200);

        // Backfill Day 4
        $response6 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-02 10:00:00',
            'symbols' => [['name' => 'UPRO', 'type' => 'STK', 'price' => '60.50']],
        ]);
        $response6->assertStatus(200);

        // Backfill Day 5
        $response7 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-03 10:00:00',
            'symbols' => [['name' => 'UPRO', 'type' => 'STK', 'price' => '60.50']],
        ]);
        $response7->assertStatus(200);

        // Verify NO overlaps
        $overlaps = $this->getOverlappingRanges($asset->id);

        // Debug if overlaps found
        if (count($overlaps) > 0) {
            dump("=== OVERLAPS FOUND ===");
            dump($overlaps);
            dump("=== ALL ASSET PRICE RECORDS ===");
            dump(AssetPrice::where('asset_id', $asset->id)->orderBy('start_dt')->get()->toArray());
        }

        $this->assertCount(0, $overlaps,
            "Found overlapping price ranges:\n" . json_encode($overlaps, JSON_PRETTY_PRINT));

        // Verify all records are contiguous
        $prices = AssetPrice::where('asset_id', $asset->id)->orderBy('start_dt')->get();
        for ($i = 0; $i < $prices->count() - 1; $i++) {
            $current = $prices[$i];
            $next = $prices[$i + 1];

            $this->assertEquals(
                $current->end_dt->format('Y-m-d'),
                $next->start_dt->format('Y-m-d'),
                "Gap or overlap found between record {$current->id} and {$next->id}"
            );
        }
    }

    /**
     * Test backfilling middle of existing range: d1-d10 exists, backfill d2-d4
     * This is a critical scenario - if a wide range exists (e.g., 2025-12-30 to 2026-01-17)
     * and we try to backfill dates in the middle, what happens?
     */
    #[Test]
    public function test_backfill_middle_of_existing_range()
    {
        // Create wide range: d1 (2025-12-30) to d10 (2026-01-08)
        $response1 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 10:00:00',
            'symbols' => [['name' => 'TQQQ', 'type' => 'STK', 'price' => '70.00']],
        ]);
        $response1->assertStatus(200);

        $asset = AssetExt::where('name', 'TQQQ')->where('data_source', $this->dataSource)->firstOrFail();

        // Verify initial record spans to 9999-12-31
        $prices = AssetPrice::where('asset_id', $asset->id)->get();
        $this->assertCount(1, $prices);
        $this->assertEquals('2025-12-30', $prices[0]->start_dt->format('Y-m-d'));
        $this->assertEquals('9999-12-31', $prices[0]->end_dt->format('Y-m-d'));

        // Extend to d10 with same price
        $response2 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-08 10:00:00',
            'symbols' => [['name' => 'TQQQ', 'type' => 'STK', 'price' => '70.00']],
        ]);
        $response2->assertStatus(200);

        // Should still be 1 record
        $prices = AssetPrice::where('asset_id', $asset->id)->get();
        $this->assertCount(1, $prices);

        // Now backfill d2 (2025-12-31) with SAME price
        $response3 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-31 10:00:00',
            'symbols' => [['name' => 'TQQQ', 'type' => 'STK', 'price' => '70.00']],
        ]);
        $response3->assertStatus(200);

        // Backfill d3 (2026-01-01)
        $response4 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-01 10:00:00',
            'symbols' => [['name' => 'TQQQ', 'type' => 'STK', 'price' => '70.00']],
        ]);
        $response4->assertStatus(200);

        // Backfill d4 (2026-01-02)
        $response5 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-02 10:00:00',
            'symbols' => [['name' => 'TQQQ', 'type' => 'STK', 'price' => '70.00']],
        ]);
        $response5->assertStatus(200);

        // CRITICAL: After backfilling middle dates, should STILL be 1 record
        // The existing wide range should absorb the backfills since price is same
        $prices = AssetPrice::where('asset_id', $asset->id)->get();

        // Verify NO overlaps
        $overlaps = $this->getOverlappingRanges($asset->id);
        if (count($overlaps) > 0) {
            dump("=== OVERLAPS FOUND ===");
            dump($overlaps);
            dump("=== ALL RECORDS ===");
            dump(AssetPrice::where('asset_id', $asset->id)->orderBy('start_dt')->get()->toArray());
        }

        $this->assertCount(0, $overlaps,
            "Found overlapping price ranges:\n" . json_encode($overlaps, JSON_PRETTY_PRINT));

        // The record should still span the full range
        $this->assertEquals('2025-12-30', $prices[0]->start_dt->format('Y-m-d'));
        $this->assertEquals('9999-12-31', $prices[0]->end_dt->format('Y-m-d'));
    }

    /**
     * Test exact start date matching
     */
    #[Test]
    public function test_exact_start_date_matching()
    {
        // Create initial record
        $response1 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 10:00:00',
            'symbols' => [['name' => 'FNGU', 'type' => 'STK', 'price' => '100.00']],
        ]);
        $response1->assertStatus(200);

        $asset = AssetExt::where('name', 'FNGU')->where('data_source', $this->dataSource)->firstOrFail();

        // Try to insert EXACT same timestamp with SAME price - should be idempotent
        $response2 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 10:00:00',
            'symbols' => [['name' => 'FNGU', 'type' => 'STK', 'price' => '100.00']],
        ]);
        $response2->assertStatus(200);

        // Should still be 1 record
        $prices = AssetPrice::where('asset_id', $asset->id)->get();
        $this->assertCount(1, $prices);

        // Verify NO overlaps
        $overlaps = $this->getOverlappingRanges($asset->id);
        $this->assertCount(0, $overlaps);
    }

    /**
     * Test exact start date with DIFFERENT price - should fail or handle gracefully
     */
    #[Test]
    public function test_exact_start_date_different_price_throws_exception()
    {
        // Create initial record
        $response1 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 10:00:00',
            'symbols' => [['name' => 'FNGD', 'type' => 'STK', 'price' => '50.00']],
        ]);
        $response1->assertStatus(200);

        // Try to insert EXACT same timestamp with DIFFERENT price - should throw exception
        $response2 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 10:00:00',
            'symbols' => [['name' => 'FNGD', 'type' => 'STK', 'price' => '55.00']],
        ]);

        // Should return error
        $response2->assertStatus(422); // Unprocessable Entity
        $this->assertStringContainsString('exact timestamp and different', $response2->json('message'));
    }

    /**
     * CRITICAL TEST: Reproduce exact production scenario from 2026-01-17
     *
     * Production sequence:
     * 1. Daily run creates record with current price (60.70) at 22:40
     * 2. Gap detection finds missing dates from 12/30 onwards
     * 3. Gap backfill at 22:56 inserts historical prices (43.67, 44.21, etc.)
     *
     * Expected: No overlaps even when backfilling earlier dates with different prices
     */
    #[Test]
    public function test_production_scenario_daily_then_gap_backfill()
    {
        DB::enableQueryLog();

        // Step 1: Daily run on 2026-01-17 at 22:40 creates current price
        // This simulates the initial daily report that might create a wide range
        $response1 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2026-01-17 22:40:57',
            'symbols' => [['name' => 'PROD_SOXL', 'type' => 'STK', 'price' => '60.70']],
        ]);
        $response1->assertStatus(200);

        $asset = AssetExt::where('name', 'PROD_SOXL')->where('data_source', $this->dataSource)->firstOrFail();

        // Check initial state - should be wide range to 9999-12-31
        $prices = AssetPrice::where('asset_id', $asset->id)->orderBy('start_dt')->get();
        dump("After daily run:", $prices->toArray());

        // Step 2: Gap backfill discovers missing date 2025-12-30
        // Historical price from IB is DIFFERENT (43.67 vs 60.70)
        $response2 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 22:56:16',
            'symbols' => [['name' => 'PROD_SOXL', 'type' => 'STK', 'price' => '43.67']],
        ]);
        $response2->assertStatus(200);

        $prices = AssetPrice::where('asset_id', $asset->id)->orderBy('start_dt')->get();
        dump("After backfill 12/30:", $prices->toArray());

        // Step 3: Gap backfill discovers another missing date 2025-12-24
        $response3 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-24 22:56:05',
            'symbols' => [['name' => 'PROD_SOXL', 'type' => 'STK', 'price' => '44.21']],
        ]);
        $response3->assertStatus(200);

        $prices = AssetPrice::where('asset_id', $asset->id)->orderBy('start_dt')->get();
        dump("After backfill 12/24:", $prices->toArray());

        // Step 4: Gap backfill discovers 2025-12-23
        $response4 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-23 22:56:00',
            'symbols' => [['name' => 'PROD_SOXL', 'type' => 'STK', 'price' => '43.70']],
        ]);
        $response4->assertStatus(200);

        $prices = AssetPrice::where('asset_id', $asset->id)->orderBy('start_dt')->get();
        dump("After backfill 12/23:", $prices->toArray());

        // CRITICAL: Check for overlaps
        $overlaps = $this->getOverlappingRanges($asset->id);

        if (count($overlaps) > 0) {
            dump("=== OVERLAPS FOUND ===");
            dump($overlaps);
            dump("=== FINAL RECORDS ===");
            dump($prices->toArray());
            dump("=== QUERIES ===");
            dump(DB::getQueryLog());
        }

        $this->assertCount(0, $overlaps,
            "PRODUCTION SCENARIO REPRODUCED: Found overlapping price ranges:\n" .
            json_encode($overlaps, JSON_PRETTY_PRINT));

        // Verify proper range structure
        for ($i = 0; $i < $prices->count() - 1; $i++) {
            $current = $prices[$i];
            $next = $prices[$i + 1];

            // Ranges should be contiguous (end of current = start of next)
            $this->assertEquals(
                $current->end_dt->format('Y-m-d'),
                $next->start_dt->format('Y-m-d'),
                "Gap or overlap between records {$current->id} (ends {$current->end_dt}) and {$next->id} (starts {$next->start_dt})"
            );
        }
    }

    /**
     * Test scenario: Same timestamp inserted twice with SAME price
     * This tests idempotency - should NOT create duplicates
     */
    #[Test]
    public function test_same_timestamp_same_price_multiple_portfolios()
    {
        // Portfolio 1 inserts at specific timestamp
        $response1 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 22:56:16',
            'symbols' => [['name' => 'MULTI_SOXL', 'type' => 'STK', 'price' => '43.67']],
        ]);
        $response1->assertStatus(200);

        $asset = AssetExt::where('name', 'MULTI_SOXL')->where('data_source', $this->dataSource)->firstOrFail();

        // Portfolio 2 inserts EXACT same timestamp and price
        // (simulating multiple portfolios processing same date)
        $response2 = $this->json('POST', '/api/asset_prices_bulk_update', [
            'source' => $this->source,
            'timestamp' => '2025-12-30 22:56:16',
            'symbols' => [['name' => 'MULTI_SOXL', 'type' => 'STK', 'price' => '43.67']],
        ]);
        $response2->assertStatus(200);

        // Should STILL have only 1 record (idempotent)
        $prices = AssetPrice::where('asset_id', $asset->id)->get();
        $this->assertCount(1, $prices,
            "Expected 1 record after inserting same timestamp/price twice, got " . $prices->count());

        // Verify no overlaps
        $overlaps = $this->getOverlappingRanges($asset->id);
        $this->assertCount(0, $overlaps);
    }

    /**
     * Helper: Find overlapping price ranges for an asset
     */
    protected function getOverlappingRanges(int $assetId): array
    {
        return DB::select("
            SELECT
                a1.id as id1,
                a1.start_dt as start1,
                a1.end_dt as end1,
                a1.price as price1,
                a2.id as id2,
                a2.start_dt as start2,
                a2.end_dt as end2,
                a2.price as price2
            FROM asset_prices a1
            JOIN asset_prices a2 ON a1.asset_id = a2.asset_id AND a1.id != a2.id
            WHERE a1.asset_id = ?
              AND a1.start_dt < a2.end_dt
              AND a1.end_dt > a2.start_dt
            ORDER BY a1.start_dt, a2.start_dt
        ", [$assetId]);
    }
}
