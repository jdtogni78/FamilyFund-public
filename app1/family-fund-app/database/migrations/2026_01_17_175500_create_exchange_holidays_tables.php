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
        Schema::create('exchange_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('exchange_code', 10)->index();  // NYSE, NASDAQ, LSE, etc.
            $table->date('holiday_date')->index();
            $table->string('holiday_name', 100);
            $table->time('early_close_time')->nullable();  // For half-days
            $table->string('source', 50);  // 'api', 'scrape', 'manual'
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['exchange_code', 'holiday_date']);
        });

        Schema::create('exchange_holiday_sync_log', function (Blueprint $table) {
            $table->id();
            $table->dateTime('sync_date');
            $table->string('exchange_code', 10);
            $table->string('source', 50);
            $table->integer('records_added')->default(0);
            $table->integer('records_updated')->default(0);
            $table->enum('status', ['success', 'partial', 'failed']);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_holiday_sync_log');
        Schema::dropIfExists('exchange_holidays');
    }
};
