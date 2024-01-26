<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPortfolioTradePort extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_portfolios', function (Blueprint $table) {
            $table->foreignId('portfolio_id')->after('account_name')->nullable()->constrained();
        });

        // update portfolio_id to be the same as portfolios.fund_id
        DB::statement("UPDATE trade_portfolios SET portfolio_id = (SELECT fund_id FROM portfolios WHERE portfolios.id = trade_portfolios.fund_id)");

        Schema::table('trade_portfolios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fund_id');
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
            $table->dropConstrainedForeignId('portfolio_id');
        });
    }
}
