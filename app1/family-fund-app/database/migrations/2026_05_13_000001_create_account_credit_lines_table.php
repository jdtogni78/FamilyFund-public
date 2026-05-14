<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_credit_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('account_id')->constrained();
            $table->decimal('principal_shares', 19, 4);
            $table->decimal('outstanding_shares', 19, 4);
            $table->unsignedInteger('term_months');
            $table->date('origination_date');
            $table->date('maturity_date');
            $table->enum('payment_frequency', ['monthly', 'quarterly', 'annual'])->default('monthly');
            $table->enum('status', ['active', 'paid_off', 'cancelled'])->default('active');
            $table->string('descr', 255)->nullable();
            $table->decimal('imputed_interest_rate', 8, 4)->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_credit_lines');
    }
};
