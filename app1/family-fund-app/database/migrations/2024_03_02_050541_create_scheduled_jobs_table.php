<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledJobsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduled_jobs', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->bigInteger('schedule_id', false, true);
            $table->string('entity_descr', 255);
            $table->bigInteger('entity_id', false, true);
            $table->date('start_dt');
            $table->date('end_dt');
            $table->datetime('deleted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // TODO copy data
//        Schema::dropIfExists('fund_report_schedules');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('scheduled_jobs');
    }
}
