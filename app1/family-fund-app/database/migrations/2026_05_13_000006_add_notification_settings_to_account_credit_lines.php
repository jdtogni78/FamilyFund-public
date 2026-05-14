<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_credit_lines', function (Blueprint $table) {
            $table->unsignedInteger('reminder_lead_days')->default(7)->after('imputed_interest_rate');
            $table->boolean('reminder_enabled')->default(true)->after('reminder_lead_days');
            $table->unsignedInteger('delay_notification_grace_days')->default(3)->after('reminder_enabled');
            $table->unsignedInteger('delay_notification_repeat_days')->nullable()->default(14)->after('delay_notification_grace_days');
            $table->unsignedInteger('delay_notification_max_repeats')->default(6)->after('delay_notification_repeat_days');
            $table->boolean('transaction_email_enabled')->default(true)->after('delay_notification_max_repeats');
            $table->boolean('mismatch_alert_enabled')->default(true)->after('transaction_email_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('account_credit_lines', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_lead_days',
                'reminder_enabled',
                'delay_notification_grace_days',
                'delay_notification_repeat_days',
                'delay_notification_max_repeats',
                'transaction_email_enabled',
                'mismatch_alert_enabled',
            ]);
        });
    }
};
