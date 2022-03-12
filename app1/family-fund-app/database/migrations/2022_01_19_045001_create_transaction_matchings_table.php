<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionMatchingsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_matchings', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->foreignId('matching_rule_id')->constrained();
            $table->foreignId('transaction_id')->constrained();
            $table->foreignId('reference_transaction_id')->references('id')->on('transactions')->constrained();
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
        Schema::drop('transaction_matchings');
    }
}
