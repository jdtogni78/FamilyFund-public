<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix template transactions to use sentinel date 9999-12-31.
     * Template transactions are those referenced by scheduled jobs with entity_descr = 'transaction'.
     * They should not appear in regular transaction history/graphs.
     */
    public function up(): void
    {
        // Get all transaction IDs that are templates (referenced by scheduled jobs)
        $templateTransactionIds = DB::table('scheduled_jobs')
            ->where('entity_descr', 'transaction')
            ->whereNull('deleted_at')
            ->pluck('entity_id')
            ->toArray();

        if (!empty($templateTransactionIds)) {
            DB::table('transactions')
                ->whereIn('id', $templateTransactionIds)
                ->update([
                    'timestamp' => '9999-12-31',
                    'shares' => null,
                    'status' => 'S', // Scheduled
                ]);
        }
    }

    /**
     * Reverse the migrations.
     * Note: Cannot fully reverse as we don't know original timestamps.
     */
    public function down(): void
    {
        // No reliable way to reverse - original timestamps are lost
    }
};
