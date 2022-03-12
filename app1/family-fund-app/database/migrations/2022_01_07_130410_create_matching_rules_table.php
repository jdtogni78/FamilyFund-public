<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatchingRulesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matching_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50);
            $table->decimal('dollar_range_start', 13, 2)->nullable()->default(0);
            $table->decimal('dollar_range_end', 13, 2)->nullable();
            $table->date('date_start');
            $table->date('date_end');
            $table->decimal('match_percent', 5, 2);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();
            // $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('matching_rules');
    }
}
