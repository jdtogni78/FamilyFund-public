<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /* 
        goals:
        * will be relate to the 4% rule.
        * have a target amount
        * have a target date
        * the 4% is configurable
        * accounts may have multiple goals
        * the progress will be displayed on the account page
        */
        Schema::create('goals', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->string('name', 30);
            $table->string('description', 1024)->nullable();
            $table->date('start_dt');
            $table->date('end_dt');
            $table->string('target_type', 10); // 'TOTAL' or '4PCT'
            $table->decimal('target_amount', 10, 2);
            $table->decimal('target_pct', 5, 2); // up to 99.99%
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
        Schema::dropIfExists('goals');
    }
};
