<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trade_band_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_id');
            $table->date('as_of');
            $table->unsignedBigInteger('scheduled_job_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('fund_id')->references('id')->on('funds');
            $table->foreign('scheduled_job_id')->references('id')->on('scheduled_jobs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_band_reports');
    }
};
