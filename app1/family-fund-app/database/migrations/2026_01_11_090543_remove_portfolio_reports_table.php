<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop portfolio_reports table (this removes the foreign key constraint)
        Schema::dropIfExists('portfolio_reports');

        // Delete scheduled_jobs with entity_descr='portfolio_report'
        DB::table('scheduled_jobs')
            ->where('entity_descr', 'portfolio_report')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate portfolio_reports table if needed
        Schema::create('portfolio_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('portfolio_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('report_type')->default('custom');
            $table->unsignedBigInteger('scheduled_job_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('portfolio_id')->references('id')->on('portfolios');
            $table->foreign('scheduled_job_id')->references('id')->on('scheduled_jobs');
        });
    }
};
