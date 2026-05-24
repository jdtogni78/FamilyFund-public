<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        //
        // Done in a single round-trip rather than N per-line existence checks:
        // pull the line IDs that already have a balance row in one query, then
        // bulk-insert the rest. (A literal insertOrIgnore on (line, start_dt)
        // would need a unique index there, but the temporal model permits two
        // rows sharing a start_dt for the same line — a same-day draw+repay
        // closes a zero-length interval — so a unique index is unsafe. Filtering
        // existing line IDs gives the same "safe to re-run on a partial restore"
        // guarantee without that constraint.)
        $existingLineIds = DB::table('account_credit_line_balances')
            ->distinct()
            ->pluck('account_credit_line_id')
            ->all();

        $now = now();
        $rows = DB::table('account_credit_lines')
            ->when($existingLineIds, fn ($q) => $q->whereNotIn('id', $existingLineIds))
            ->get()
            ->map(fn ($line) => [
                'account_credit_line_id' => $line->id,
                'outstanding_shares'     => $line->outstanding_shares,
                'start_dt'               => $line->origination_date,
                'end_dt'                 => '9999-12-31',
                'transaction_id'         => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ])
            ->all();

        if (! empty($rows)) {
            DB::table('account_credit_line_balances')->insert($rows);
        }

        // Record what was backfilled via the log channel (respects config and
        // does not pollute test-runner stdout the way fwrite(STDOUT) did).
        Log::info(sprintf(
            'account_credit_line_balances backfill: inserted %d row(s)',
            count($rows)
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('account_credit_line_balances');
    }
};
