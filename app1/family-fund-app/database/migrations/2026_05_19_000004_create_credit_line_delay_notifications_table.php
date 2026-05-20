<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delay-notification ledger: one row per delay email actually sent for a
 * given schedule row. Replaces the Cache-key counter that
 * ScanLatePaymentsJob used to dedup, which lost state on cache flush /
 * across multiple queue workers (Issue #7).
 *
 * The unique index on (credit_line_payment_id, notification_number) makes
 * a concurrent double-send safely fail at the DB level — the losing
 * worker rolls back its insert and skips the Mail::send.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_line_delay_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('credit_line_payment_id')->constrained('credit_line_payments');
            $table->unsignedInteger('notification_number'); // 1-based sequence per payment
            $table->timestamp('sent_at')->useCurrent();
            $table->string('recipient_email')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['credit_line_payment_id', 'notification_number'], 'credit_line_delay_notif_unique');
            $table->index('credit_line_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_line_delay_notifications');
    }
};
