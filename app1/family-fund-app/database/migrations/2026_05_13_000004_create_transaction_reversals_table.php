<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_reversals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('transaction_id')->unique()->constrained('transactions');
            $table->foreignId('original_target_credit_line_id')->nullable()->constrained('account_credit_lines');
            $table->dateTime('reversed_at');
            $table->foreignId('reversed_by_user_id')->constrained('users');
            $table->string('reason', 1024);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_reversals');
    }
};
