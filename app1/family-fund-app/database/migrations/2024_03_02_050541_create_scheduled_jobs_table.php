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

        Schema::table('fund_reports', function (Blueprint $table) {
            // rename the fund_report_schedule_id column to scheduled_job_id
            $table->foreignId('scheduled_job_id')->after('as_of')->nullable()
                ->constrained()->value('fund_report_schedule_id');
        });

        Schema::table('fund_reports', function (Blueprint $table) {
            // rename the fund_report_schedule_id column to scheduled_job_id
            $table->dropForeignId('fund_report_schedule_id');
        });

        DB::raw("insert into scheduled_jobs (schedule_id, entity_descr, entity_id, start_dt, end_dt, created_at, updated_at, deleted_at) " .
                "select schedule_id, 'fund_report', fund_report_id, start_dt, end_dt, created_at, updated_at, deleted_at from fund_report_schedules ");

        Schema::table('transactions', function (Blueprint $table) {
            // drop the fund_report_schedule_id column
            $table->foreignId('scheduled_job_id')->after('flags')->nullable()->constrained();
            $table->datetime('timestamp')->change();
        });

        // drop the fund_report_schedules table
        Schema::dropIfExists('fund_report_schedules');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // create the fund_report_schedules table
        Schema::create('fund_report_schedules', function (Blueprint $table) {
            $table->bigInteger('schedule_id', true, true);
            $table->bigInteger('fund_report_id', false, true);
            $table->date('start_dt');
            $table->date('end_dt');
            $table->datetime('deleted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::raw("insert into fund_report_schedules (schedule_id, fund_report_id, start_dt, end_dt, created_at, updated_at, deleted_at) " .
                "select schedule_id, entity_id, start_dt, end_dt, created_at, updated_at, deleted_at from fund_report_schedules " .
                "where entity_descr = 'fund_report'");

        Schema::table('fund_reports', function (Blueprint $table) {
            // rename the fund_report_schedule_id column to scheduled_job_id
            $table->foreignId('fund_report_schedule_id')->after('as_of')->nullable()
                ->constrained()->value('scheduled_job_id');
        });

        Schema::table('fund_reports', function (Blueprint $table) {
            // rename the fund_report_schedule_id column to scheduled_job_id
            $table->dropForeignId('scheduled_job_id');
        });

        // drop the scheduled_jobs table
        Schema::dropIfExists('scheduled_jobs');

        // rollback renameColumn
        Schema::table('fund_reports', function (Blueprint $table) {
            $table->renameColumn('scheduled_job_id', 'fund_report_schedule_id');
        });
    }
}
