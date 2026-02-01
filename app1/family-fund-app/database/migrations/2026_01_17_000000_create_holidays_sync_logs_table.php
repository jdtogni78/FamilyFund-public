<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHolidaysSyncLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('holidays_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_job_id')->constrained('scheduled_jobs');
            $table->string('exchange', 10);
            $table->dateTime('synced_at');
            $table->integer('records_synced')->default(0);
            $table->string('source', 50);
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
        Schema::dropIfExists('holidays_sync_logs');
    }
}
