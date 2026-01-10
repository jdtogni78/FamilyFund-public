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
        if (!Schema::hasTable('portfolio_reports')) {
            Schema::create('portfolio_reports', function (Blueprint $table) {
                $table->bigInteger('id', true, true);
                $table->foreignId('portfolio_id')->constrained();
                $table->date('start_date');
                $table->date('end_date');
                $table->foreignId('scheduled_job_id')->nullable()->constrained();
                $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
                $table->timestamp('created_at')->useCurrent();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_reports');
    }
};
