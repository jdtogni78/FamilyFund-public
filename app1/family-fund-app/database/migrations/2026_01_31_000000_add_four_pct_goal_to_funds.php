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
        Schema::table('funds', function (Blueprint $table) {
            $table->decimal('four_pct_yearly_expenses', 12, 2)->nullable()->after('goal');
            $table->decimal('four_pct_net_worth_pct', 5, 2)->nullable()->default(100.00)->after('four_pct_yearly_expenses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->dropColumn(['four_pct_yearly_expenses', 'four_pct_net_worth_pct']);
        });
    }
};
