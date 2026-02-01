<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Fix overlapping asset price ranges after deduplication.
     *
     * When duplicate assets were merged, their price histories were combined.
     * This can result in overlapping date ranges for the same asset.
     *
     * This migration deletes the duplicate/overlapping price records,
     * keeping the one with the lowest ID (oldest).
     */
    public function up(): void
    {
        // Find all overlapping price pairs
        $overlaps = DB::select("
            SELECT DISTINCT
                a1.asset_id,
                a1.id as id1,
                a1.start_dt as start1,
                a1.end_dt as end1,
                a2.id as id2,
                a2.start_dt as start2,
                a2.end_dt as end2
            FROM asset_prices a1
            JOIN asset_prices a2 ON a1.asset_id = a2.asset_id AND a1.id < a2.id
            WHERE a1.start_dt < a2.end_dt
              AND a1.end_dt > a2.start_dt
            ORDER BY a1.asset_id, a1.id
        ");

        if (empty($overlaps)) {
            Log::info("No overlapping asset prices found");
            return;
        }

        Log::info("Found " . count($overlaps) . " overlapping price pairs to fix");

        // Collect IDs to delete (keep the lower ID, delete the higher one)
        $toDelete = [];
        foreach ($overlaps as $overlap) {
            $toDelete[] = $overlap->id2;
        }
        $toDelete = array_unique($toDelete);

        // Delete in chunks
        $chunks = array_chunk($toDelete, 100);
        $totalDeleted = 0;
        foreach ($chunks as $chunk) {
            $deleted = DB::table('asset_prices')
                ->whereIn('id', $chunk)
                ->delete();
            $totalDeleted += $deleted;
        }

        Log::info("Deleted {$totalDeleted} overlapping price records");
    }

    public function down(): void
    {
        Log::warning("Cannot restore deleted overlapping prices - manual restoration from backup required");
    }
};
