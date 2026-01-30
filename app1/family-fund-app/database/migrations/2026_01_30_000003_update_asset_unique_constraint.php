<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Phase 3: Update unique constraint.
     *
     * Change unique key from (source, name, type) to (data_source, name, type).
     * This must run AFTER deduplication to avoid constraint violations.
     */
    public function up(): void
    {
        // Check if old unique constraint exists and drop it
        $indexes = DB::select("SHOW INDEX FROM assets WHERE Key_name = 'unique_asset'");
        if (!empty($indexes)) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropUnique('unique_asset');
            });
        }

        // Add new unique constraint on (data_source, name, type)
        Schema::table('assets', function (Blueprint $table) {
            $table->unique(['data_source', 'name', 'type'], 'unique_asset_data_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if new constraint exists and drop it
        $indexes = DB::select("SHOW INDEX FROM assets WHERE Key_name = 'unique_asset_data_source'");
        if (!empty($indexes)) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropUnique('unique_asset_data_source');
            });
        }

        // Restore old constraint (may fail if duplicates exist)
        Schema::table('assets', function (Blueprint $table) {
            $table->unique(['source', 'name', 'type'], 'unique_asset');
        });
    }
};
