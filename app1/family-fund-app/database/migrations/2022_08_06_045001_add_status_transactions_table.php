<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusTransactionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("UPDATE transactions SET type = 'MAT' WHERE source = 'MAT'");
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('status', 1)->default('P')->after('type');
            $table->dropColumn('source');
        });
        DB::statement("UPDATE transactions SET status = 'C'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('source', 3)->default('DIR')->after('id');
            $table->dropColumn('status');
        });
        DB::statement("UPDATE transactions SET source = 'MAT' WHERE type = 'MAT'");
        DB::statement("UPDATE transactions SET source = 'SPO' WHERE type = 'INI'");
    }
}
