<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundReportSchedulesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_report_schedules', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->foreignId('fund_report_id')->constrained();
            $table->foreignId('schedule_id')->constrained('report_schedules');
            $table->date('start_dt');
            $table->date('end_dt');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('fund_report_schedules');
    }
}
