<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashDepositsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_deposits', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->date('date')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 13, 2);
            $table->enum('status', ['PENDING', 'DEPOSITED', 'ALLOCATED']);
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions');
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
        Schema::drop('cash_deposits');
    }
}
