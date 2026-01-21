<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            // Drop the old unique constraint on source
            $table->dropUnique(['source']);

            // Add new composite unique constraint on (fund_id, source)
            $table->unique(['fund_id', 'source'], 'portfolios_fund_source_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            // Drop the composite constraint
            $table->dropUnique('portfolios_fund_source_unique');

            // Restore the old unique constraint on source alone
            $table->unique('source');
        });
    }
};
