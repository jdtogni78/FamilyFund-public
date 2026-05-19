<?php

use App\Services\CreditLine\Adjust\PaymentGenerationRepairer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make credit-line payment supersession unambiguous.
 *
 * Adds credit_line_payments.credit_line_adjustment_id — the generation that
 * owns the row (NULL == origination, the schedule built at draw time).
 * Generations are chained in adjustment write order; a row is `cancelled`
 * iff a later generation supersedes its slot. With this stamp, "which plan
 * was in force on date D" is deterministic without created_at heuristics.
 *
 * Then runs {@see PaymentGenerationRepairer} once to retire the legacy scar:
 * it backfills generation stamps on rows that predate the column and restores
 * rows the pre-fix forward-dating bug wrongly cancelled before their effective
 * date. The repairer is conservative (never deletes, never double-bills) and
 * idempotent, so this is safe on every environment and re-runnable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_line_payments', function (Blueprint $table) {
            $table->foreignId('credit_line_adjustment_id')
                ->nullable()
                ->after('account_credit_line_id')
                ->constrained('credit_line_adjustments')
                ->nullOnDelete();
            // ->constrained() already creates the supporting index.
        });

        // Retire the legacy scar in the same deploy step (previously a manual,
        // dry-run-only console command). Idempotent — re-running is a no-op.
        (new PaymentGenerationRepairer())->repair(true);
    }

    public function down(): void
    {
        Schema::table('credit_line_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_line_adjustment_id');
        });
    }
};
