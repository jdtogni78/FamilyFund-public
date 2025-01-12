<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // delete duplicates
        $amrs = DB::table('account_matching_rules')
            ->groupBy('account_id', 'matching_rule_id')
            ->havingRaw('COUNT(*) > 1')
            ->get(['account_id', 'matching_rule_id']);
        Log::info($amrs);
        foreach ($amrs as $amr) {
            Log::info(json_encode($amr));
            $found = DB::table('account_matching_rules')
                ->where('account_id', $amr->account_id)
                ->where('matching_rule_id', $amr->matching_rule_id)
                ->orderBy('id', 'asc')
                ->get();
            unset($found[0]);
            foreach ($found as $f) {
                Log::info("Deleting: " . json_encode($f));
                DB::table('account_matching_rules')->where('id', $f->id)->delete();
            }
        }
        Schema::table('account_matching_rules', function (Blueprint $table) {
            $table->unique(['account_id', 'matching_rule_id']);
        });
    }

    public function down()
    {
        Schema::table('account_matching_rules', function (Blueprint $table) {
            $table->dropUnique(['account_id', 'matching_rule_id']);
        });
    }
};