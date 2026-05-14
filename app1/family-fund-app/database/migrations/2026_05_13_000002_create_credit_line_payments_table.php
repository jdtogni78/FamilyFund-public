<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_line_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('account_credit_line_id')->constrained('account_credit_lines');
            $table->date('due_date');
            $table->decimal('shares_due', 19, 4);
            $table->enum('status', ['scheduled', 'paid', 'partial', 'late', 'cancelled'])->default('scheduled');
            $table->foreignId('paid_transaction_id')->nullable()->constrained('transactions');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['account_credit_line_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_line_payments');
    }
};
