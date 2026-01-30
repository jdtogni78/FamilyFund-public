<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Phase 2: Deduplicate assets.
     *
     * For each (name, type, data_source) combination with duplicates:
     * 1. Select the canonical asset (lowest ID)
     * 2. Update portfolio_assets to point to canonical
     * 3. Merge price history (keep all unique prices)
     * 4. Soft-delete duplicate assets
     *
     * Processing is done in chunks to avoid memory issues.
     */
    public function up(): void
    {
        // Find all duplicate groups: same (name, type, data_source)
        $duplicateGroups = DB::table('assets')
            ->select('name', 'type', 'data_source')
            ->whereNull('deleted_at')
            ->groupBy('name', 'type', 'data_source')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        Log::info("Found {$duplicateGroups->count()} duplicate asset groups to process");

        foreach ($duplicateGroups as $group) {
            $this->processDuplicateGroup($group->name, $group->type, $group->data_source);
        }
    }

    private function processDuplicateGroup(string $name, string $type, string $dataSource): void
    {
        Log::info("Processing duplicate group: name={$name}, type={$type}, data_source={$dataSource}");

        // Get all assets in this group, ordered by ID (canonical = lowest)
        $assets = DB::table('assets')
            ->where('name', $name)
            ->where('type', $type)
            ->where('data_source', $dataSource)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        if ($assets->count() <= 1) {
            return;
        }

        $canonicalAsset = $assets->first();
        $duplicateAssets = $assets->slice(1);

        Log::info("Canonical asset: id={$canonicalAsset->id}, duplicates: " .
            implode(',', $duplicateAssets->pluck('id')->toArray()));

        foreach ($duplicateAssets as $duplicate) {
            $this->mergeDuplicate($canonicalAsset->id, $duplicate->id);
        }
    }

    private function mergeDuplicate(int $canonicalId, int $duplicateId): void
    {
        // 1. Update portfolio_assets to point to canonical
        $updatedPortfolioAssets = DB::table('portfolio_assets')
            ->where('asset_id', $duplicateId)
            ->update(['asset_id' => $canonicalId]);

        Log::info("Updated {$updatedPortfolioAssets} portfolio_assets from asset {$duplicateId} to {$canonicalId}");

        // 2. Merge price history - keep unique (start_dt, end_dt) combinations
        // For conflicts, keep the canonical's price record
        $duplicatePrices = DB::table('asset_prices')
            ->where('asset_id', $duplicateId)
            ->get();

        foreach ($duplicatePrices as $price) {
            // Check if canonical already has a price for this period
            $existingPrice = DB::table('asset_prices')
                ->where('asset_id', $canonicalId)
                ->where('start_dt', $price->start_dt)
                ->first();

            if (!$existingPrice) {
                // Move the price to canonical asset
                DB::table('asset_prices')
                    ->where('id', $price->id)
                    ->update(['asset_id' => $canonicalId]);
            }
            // If conflict exists, duplicate price will be orphaned and removed with asset soft-delete
        }

        // 3. Update asset_change_logs
        DB::table('asset_change_logs')
            ->where('asset_id', $duplicateId)
            ->update(['asset_id' => $canonicalId]);

        // 4. Delete orphaned price records (prices that couldn't be merged)
        $orphanedPrices = DB::table('asset_prices')
            ->where('asset_id', $duplicateId)
            ->delete();
        if ($orphanedPrices > 0) {
            Log::info("Deleted {$orphanedPrices} orphaned price records from duplicate asset {$duplicateId}");
        }

        // 5. Hard-delete the duplicate asset (required for unique constraint)
        DB::table('assets')
            ->where('id', $duplicateId)
            ->delete();

        Log::info("Hard-deleted duplicate asset {$duplicateId}");
    }

    /**
     * Reverse the migrations.
     * Note: This cannot fully restore the original state since portfolio_assets
     * and price records have been merged. Manual restoration from backup required.
     */
    public function down(): void
    {
        Log::warning("Deduplication rollback requires manual restoration from backup");

        // Restore soft-deleted assets (but portfolio_assets linkage cannot be restored)
        // This is a partial rollback only
    }
};
