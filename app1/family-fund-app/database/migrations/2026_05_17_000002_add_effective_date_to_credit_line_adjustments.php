<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Record the start date a readjustment's new schedule is anchored to.
 *
 * Previously a readjustment always re-anchored the fresh amortization
 * schedule to "today". Admins now choose an explicit start date, so the
 * audit row must capture it for the adjustment history (UC-40/UC-41).
 *
 * Added nullable; existing audit rows were all effectively anchored to
 * their adjusted_at date, so backfill from that to keep history truthful.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_line_adjustments', function (Blueprint $table) {
            $table->date('effective_date')->nullable()->after('adjusted_at');
        });

        DB::table('credit_line_adjustments')
            ->whereNull('effective_date')
            ->update(['effective_date' => DB::raw('DATE(adjusted_at)')]);
    }

    public function down(): void
    {
        Schema::table('credit_line_adjustments', function (Blueprint $table) {
            $table->dropColumn('effective_date');
        });
    }
};
