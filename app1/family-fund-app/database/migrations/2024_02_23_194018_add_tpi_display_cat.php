<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTPIDisplayCat extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_portfolio_items', function (Blueprint $table) {
            $table->string('display_category', 50)->after('deviation_trigger')->nullable();
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
            $table->dropColumn('display_category');
        });
    }
}
