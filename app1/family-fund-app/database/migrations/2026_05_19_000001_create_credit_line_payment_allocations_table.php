<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allocation ledger: records how many shares of a REP transaction were
 * applied to a specific credit_line_payments schedule row.
 *
 * One transaction -> many rows; one row -> many transactions. Replaces the
 * single credit_line_payments.paid_transaction_id column as the unit of
 * truth (that column is kept as a derived/denormalised value).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_line_payment_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('credit_line_payment_id')->constrained('credit_line_payments');
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->decimal('shares', 19, 4);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->index('credit_line_payment_id');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_line_payment_allocations');
    }
};
