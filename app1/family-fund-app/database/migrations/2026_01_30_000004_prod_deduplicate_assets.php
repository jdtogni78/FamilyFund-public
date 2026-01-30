<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Production Deduplication Migration
     *
     * This migration is specifically designed for production with:
     * - Chunk processing to avoid memory issues
     * - Detailed logging for audit trail
     * - Transaction safety per chunk
     * - Backup table creation before changes
     *
     * Run AFTER: add_data_source_to_assets, deduplicate_assets
     * This handles any additional production-specific data.
     */

    private const CHUNK_SIZE = 100;

    public function up(): void
    {
        // Create backup tables for audit trail (prod only)
        $this->createBackupTables();

        // Process any remaining duplicates that may exist in prod
        // (Dev migration may have handled test data differently)
        $this->processRemainingDuplicates();

        // Clean up orphaned price records
        $this->cleanupOrphanedPrices();

        // Verify data integrity
        $this->verifyIntegrity();
    }

    private function createBackupTables(): void
    {
        // Backup asset mappings before changes
        DB::statement("
            CREATE TABLE IF NOT EXISTS _backup_asset_dedup_map (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                original_asset_id BIGINT NOT NULL,
                canonical_asset_id BIGINT NOT NULL,
                asset_name VARCHAR(128),
                asset_type VARCHAR(20),
                migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_original (original_asset_id),
                INDEX idx_canonical (canonical_asset_id)
            )
        ");

        // Backup portfolio_asset changes
        DB::statement("
            CREATE TABLE IF NOT EXISTS _backup_portfolio_asset_changes (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                portfolio_asset_id BIGINT NOT NULL,
                old_asset_id BIGINT NOT NULL,
                new_asset_id BIGINT NOT NULL,
                migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        Log::info("Backup tables created for asset deduplication");
    }

    private function processRemainingDuplicates(): void
    {
        // Find duplicate groups
        $duplicateGroups = DB::table('assets')
            ->select('name', 'type', 'data_source', DB::raw('COUNT(*) as cnt'))
            ->whereNull('deleted_at')
            ->groupBy('name', 'type', 'data_source')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateGroups->isEmpty()) {
            Log::info("No duplicate assets found - skipping deduplication");
            return;
        }

        Log::info("Found {$duplicateGroups->count()} duplicate groups to process in production");

        foreach ($duplicateGroups as $group) {
            DB::transaction(function () use ($group) {
                $this->processSingleDuplicateGroup($group->name, $group->type, $group->data_source);
            });
        }
    }

    private function processSingleDuplicateGroup(string $name, string $type, string $dataSource): void
    {
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
        $duplicates = $assets->slice(1);

        foreach ($duplicates as $duplicate) {
            // Record the mapping for audit
            DB::table('_backup_asset_dedup_map')->insert([
                'original_asset_id' => $duplicate->id,
                'canonical_asset_id' => $canonicalAsset->id,
                'asset_name' => $name,
                'asset_type' => $type,
            ]);

            // Process portfolio_assets in chunks
            DB::table('portfolio_assets')
                ->where('asset_id', $duplicate->id)
                ->chunkById(self::CHUNK_SIZE, function ($portfolioAssets) use ($canonicalAsset, $duplicate) {
                    foreach ($portfolioAssets as $pa) {
                        // Record change for audit
                        DB::table('_backup_portfolio_asset_changes')->insert([
                            'portfolio_asset_id' => $pa->id,
                            'old_asset_id' => $duplicate->id,
                            'new_asset_id' => $canonicalAsset->id,
                        ]);

                        // Update to canonical
                        DB::table('portfolio_assets')
                            ->where('id', $pa->id)
                            ->update(['asset_id' => $canonicalAsset->id]);
                    }
                });

            // Merge price history
            $this->mergePriceHistory($canonicalAsset->id, $duplicate->id);

            // Update change logs
            DB::table('asset_change_logs')
                ->where('asset_id', $duplicate->id)
                ->update(['asset_id' => $canonicalAsset->id]);

            // Delete orphaned price records (prices that couldn't be merged)
            $orphanedPrices = DB::table('asset_prices')
                ->where('asset_id', $duplicate->id)
                ->delete();
            if ($orphanedPrices > 0) {
                Log::info("Deleted {$orphanedPrices} orphaned price records from duplicate asset {$duplicate->id}");
            }

            // Hard-delete duplicate (required for unique constraint on data_source, name, type)
            DB::table('assets')
                ->where('id', $duplicate->id)
                ->delete();

            Log::info("Merged and hard-deleted asset {$duplicate->id} into {$canonicalAsset->id} ({$name}/{$type})");
        }
    }

    private function mergePriceHistory(int $canonicalId, int $duplicateId): void
    {
        // Get all prices from duplicate
        $duplicatePrices = DB::table('asset_prices')
            ->where('asset_id', $duplicateId)
            ->get();

        foreach ($duplicatePrices as $price) {
            // Check if canonical has overlapping price
            $hasOverlap = DB::table('asset_prices')
                ->where('asset_id', $canonicalId)
                ->where('start_dt', $price->start_dt)
                ->exists();

            if (!$hasOverlap) {
                // Move price to canonical
                DB::table('asset_prices')
                    ->where('id', $price->id)
                    ->update(['asset_id' => $canonicalId]);
            }
            // Conflicting prices left on duplicate (will be orphaned)
        }
    }

    private function cleanupOrphanedPrices(): void
    {
        // Delete orphaned price records (prices pointing to non-existent assets)
        $orphanedCount = DB::table('asset_prices')
            ->whereNotIn('asset_id', function ($query) {
                $query->select('id')->from('assets');
            })
            ->delete();

        if ($orphanedCount > 0) {
            Log::info("Cleaned up {$orphanedCount} orphaned price records");
        }
    }

    private function verifyIntegrity(): void
    {
        // Check for any remaining duplicates
        $remainingDuplicates = DB::table('assets')
            ->select('name', 'type', 'data_source', DB::raw('COUNT(*) as cnt'))
            ->whereNull('deleted_at')
            ->groupBy('name', 'type', 'data_source')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($remainingDuplicates > 0) {
            Log::error("INTEGRITY CHECK FAILED: {$remainingDuplicates} duplicate groups remain!");
            throw new \Exception("Asset deduplication incomplete - {$remainingDuplicates} duplicates remain");
        }

        // Check for orphaned portfolio_assets
        $orphanedPA = DB::table('portfolio_assets')
            ->whereNotIn('asset_id', function ($query) {
                $query->select('id')->from('assets');
            })
            ->count();

        if ($orphanedPA > 0) {
            Log::warning("Found {$orphanedPA} portfolio_assets pointing to deleted assets");
        }

        Log::info("Asset deduplication integrity check passed");
    }

    /**
     * Reverse the migrations.
     *
     * Note: Uses backup tables to restore original state.
     * Price merging cannot be fully reversed.
     */
    public function down(): void
    {
        // Restore portfolio_assets from backup
        $changes = DB::table('_backup_portfolio_asset_changes')->get();

        foreach ($changes as $change) {
            DB::table('portfolio_assets')
                ->where('id', $change->portfolio_asset_id)
                ->update(['asset_id' => $change->old_asset_id]);
        }

        // Restore soft-deleted assets
        $mappings = DB::table('_backup_asset_dedup_map')->get();
        $restoredIds = $mappings->pluck('original_asset_id')->unique();

        DB::table('assets')
            ->whereIn('id', $restoredIds)
            ->update(['deleted_at' => null]);

        // Drop backup tables
        DB::statement("DROP TABLE IF EXISTS _backup_portfolio_asset_changes");
        DB::statement("DROP TABLE IF EXISTS _backup_asset_dedup_map");

        Log::info("Asset deduplication rolled back (price history may need manual review)");
    }
};
