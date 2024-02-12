<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFundReportSchedule extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_reports', function (Blueprint $table) {
            $table->foreignId('fund_report_schedule_id')->after('as_of')->nullable()->constrained();
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
            $table->dropConstrainedForeignId('fund_report_schedule_id');
        });
    }
}
