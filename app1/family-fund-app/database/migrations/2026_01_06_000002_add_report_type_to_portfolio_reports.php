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
        Schema::table('portfolio_reports', function (Blueprint $table) {
            // Report type: custom, quarterly, annual
            // - custom: uses explicit start/end dates
            // - quarterly: calculates previous quarter from run date
            // - annual: calculates previous year from run date
            $table->string('report_type', 20)->default('custom')->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolio_reports', function (Blueprint $table) {
            $table->dropColumn('report_type');
        });
    }
};
