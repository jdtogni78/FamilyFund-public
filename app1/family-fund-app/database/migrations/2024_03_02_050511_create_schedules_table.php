<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->string('descr', 255);
            $table->string('type', 3);
            $table->string('value', 255);
            $table->datetime('deleted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
// TODO migrate
//        Schema::dropIfExists('report_schedules');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('schedules');
    }
}
