<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `applies_to_rep` per-rule toggle to `matching_rules`.
 *
 * See docs/credit_lines/matching_on_repayment.md — the trustee may
 * enable/disable contribution matching on credit-line REP transactions
 * per rule. Default TRUE per the user's "in principle, yes" preference;
 * trustees opt OUT per rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matching_rules', function (Blueprint $table) {
            $table->boolean('applies_to_rep')->default(true)->after('match_percent');
        });
    }

    public function down(): void
    {
        Schema::table('matching_rules', function (Blueprint $table) {
            $table->dropColumn('applies_to_rep');
        });
    }
};
