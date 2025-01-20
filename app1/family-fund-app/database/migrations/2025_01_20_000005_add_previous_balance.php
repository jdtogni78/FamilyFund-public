<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\AccountBalance;


return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_balances', function (Blueprint $table) {
            $table->unsignedBigInteger('previous_balance_id')->nullable()->after('shares');
        });

        $balances = AccountBalance::all();
        foreach ($balances as $balance) {
            $previousBalance = AccountBalance::where('account_id', $balance->account_id)
                ->where('end_dt', '=', $balance->start_dt)
                ->where('shares', '<', $balance->shares)
                ->orderBy('start_dt', 'desc')
                ->orderBy('shares', 'desc')
                ->first();
            if ($previousBalance != null) {
                $balance->previous_balance_id = $previousBalance->id;
                $balance->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_balances', function (Blueprint $table) {
            $table->dropColumn('previous_balance_id');
        });
    }
};
