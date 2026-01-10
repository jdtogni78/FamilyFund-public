<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Set disbursement_cap = 0 for accounts that should not show disbursement section.
     */
    public function up(): void
    {
        // Accounts that should NOT show disbursement
        DB::table('accounts')
            ->whereIn('id', [22, 26])
            ->update(['disbursement_cap' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('accounts')
            ->whereIn('id', [22, 26])
            ->update(['disbursement_cap' => null]);
    }
};
