<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTradePortfolioDates extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_portfolios', function (Blueprint $table) {
            $table->date('start_dt')->default(DB::raw('curdate()'));
            $table->date('end_dt')->default('9999-12-31');
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
            $table->dropColumn('start_dt');
            $table->dropColumn('end_dt');
        });
    }
}
