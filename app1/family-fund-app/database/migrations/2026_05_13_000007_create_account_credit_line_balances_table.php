<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Temporal history table for AccountCreditLine.outstanding_shares.
 *
 * Mirrors the shape of account_balances:
 *   - One row per (line, [start_dt, end_dt)) interval.
 *   - The active row has end_dt = '9999-12-31'.
 *   - transaction_id points to the BOR/REP that caused the change.
 *
 * Used by FundReceivableCalculator to reconstruct the receivable as of any
 * historical date (instead of always returning the current value of
 * account_credit_lines.outstanding_shares).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_credit_line_balances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('account_credit_line_id')->constrained('account_credit_lines');
            $table->decimal('outstanding_shares', 19, 4);
            $table->date('start_dt')->default(DB::raw('curdate()'));
            $table->date('end_dt')->default('9999-12-31');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['account_credit_line_id', 'start_dt', 'end_dt'], 'acl_balances_line_dt_idx');
        });

        // Idempotent backfill: one active row per existing AccountCreditLine
        // with the current outstanding_shares, starting at origination_date.
        // Only inserts where no row exists yet, so re-running this migration
        // (e.g., on a partial restore) is safe.
        $lines = DB::table('account_credit_lines')->get();
        $now = now();
        $rowCount = 0;
        foreach ($lines as $line) {
            $exists = DB::table('account_credit_line_balances')
                ->where('account_credit_line_id', $line->id)
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('account_credit_line_balances')->insert([
                'account_credit_line_id' => $line->id,
                'outstanding_shares'     => $line->outstanding_shares,
                'start_dt'               => $line->origination_date,
                'end_dt'                 => '9999-12-31',
                'transaction_id'         => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
            $rowCount++;
        }
        // Print a one-liner so the migration output records what was backfilled.
        if (function_exists('fwrite')) {
            @fwrite(STDOUT, "  backfilled {$rowCount} account_credit_line_balances row(s)\n");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_credit_line_balances');
    }
};
