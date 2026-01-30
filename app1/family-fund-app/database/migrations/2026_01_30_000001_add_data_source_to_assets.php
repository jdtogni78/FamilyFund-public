<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 1: Add data_source column to assets table.
     * This represents the actual data provider (IB, MANUAL, etc.) rather than
     * the portfolio identifier stored in 'source'.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('data_source', 30)->after('source')->default('IB');
        });

        // Populate data_source based on existing source patterns
        // IB sources: *IB, MONARCH_* (all get prices from Interactive Brokers)
        // MANUAL: Cash type assets
        DB::statement("UPDATE assets SET data_source = 'MANUAL' WHERE type = 'CSH'");

        // Everything else defaults to 'IB' via the column default
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('data_source');
        });
    }
};
