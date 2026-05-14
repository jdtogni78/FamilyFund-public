<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_line_adjustments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('account_credit_line_id')->constrained('account_credit_lines');
            $table->dateTime('adjusted_at');
            $table->foreignId('adjusted_by_user_id')->nullable()->constrained('users');
            $table->decimal('outstanding_shares_at_adjustment', 19, 4);
            $table->unsignedInteger('old_term_months');
            $table->unsignedInteger('new_term_months');
            $table->enum('old_payment_frequency', ['monthly', 'quarterly', 'annual']);
            $table->enum('new_payment_frequency', ['monthly', 'quarterly', 'annual']);
            $table->date('old_maturity_date');
            $table->date('new_maturity_date');
            $table->date('old_planned_payoff_date');
            $table->date('new_planned_payoff_date');
            $table->string('reason', 1024)->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->index('account_credit_line_id');
            $table->index('adjusted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_line_adjustments');
    }
};
