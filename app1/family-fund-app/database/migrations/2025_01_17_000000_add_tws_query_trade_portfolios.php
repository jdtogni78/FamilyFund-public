<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTwsQueryTradePortfolios extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_portfolios', function (Blueprint $table) {
            $table->string('tws_query_id', 50)->nullable()->after('portfolio_id');
            $table->string('tws_token', 100)->nullable()->after('tws_query_id');
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
            $table->dropColumn('tws_query_id');
            $table->dropColumn('tws_token');
        });
    }
}
