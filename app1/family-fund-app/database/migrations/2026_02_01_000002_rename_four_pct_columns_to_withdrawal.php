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
            $table->renameColumn('four_pct_yearly_expenses', 'withdrawal_yearly_expenses');
            $table->renameColumn('four_pct_net_worth_pct', 'withdrawal_net_worth_pct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->renameColumn('withdrawal_yearly_expenses', 'four_pct_yearly_expenses');
            $table->renameColumn('withdrawal_net_worth_pct', 'four_pct_net_worth_pct');
        });
    }
};
