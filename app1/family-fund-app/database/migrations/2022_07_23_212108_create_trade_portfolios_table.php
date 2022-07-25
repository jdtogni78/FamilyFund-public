

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradeportfoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_portfolios', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->string('account_name', 50);
            $table->decimal('cash_target', 5, 2)->default('0.15');
            $table->decimal('cash_reserve_target', 5, 2)->default('0.05');
            $table->decimal('max_single_order', 5, 2)->default('0.20');
            $table->decimal('minimum_order', 13, 2)->default('100.00');
            $table->integer('rebalance_period')->default('90');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();
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
        Schema::dropIfExists('trade_portfolios');
    }
}

