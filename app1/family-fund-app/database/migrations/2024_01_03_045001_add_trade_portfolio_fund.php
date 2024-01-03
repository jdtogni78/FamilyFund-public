<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTradePortfolioFund extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_portfolios', function (Blueprint $table) {
            $table->string('fund_id', 1)->after('account_name');
            $table->dropColumn('source');
        });
        DB::statement("UPDATE trade_portfolios SET fund_id = (SELECT id FROM funds LIMIT 1)");
        Schema::table('trade_portfolios', function (Blueprint $table) {
            $table->foreignId('fund_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_portfolios', function (Blueprint $table) {
            $table->dropColumn('fund_id');
        });
    }
}
