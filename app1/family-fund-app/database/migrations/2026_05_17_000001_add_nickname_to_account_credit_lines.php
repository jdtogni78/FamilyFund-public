<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add a human-friendly `nickname` to credit lines. Mandatory for new draws
 * (enforced in CreateAccountCreditLineRequest); the column is added nullable
 * so existing rows can be backfilled with a sensible default before the app
 * starts requiring it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_credit_lines', function (Blueprint $table) {
            $table->string('nickname', 255)->nullable()->after('account_id');
        });

        // Backfill existing rows so they read sensibly in the listing and
        // the new mandatory filter never sees a blank label.
        DB::table('account_credit_lines')
            ->whereNull('nickname')
            ->update(['nickname' => DB::raw("CONCAT('Credit line #', id)")]);
    }

    public function down(): void
    {
        Schema::table('account_credit_lines', function (Blueprint $table) {
            $table->dropColumn('nickname');
        });
    }
};
