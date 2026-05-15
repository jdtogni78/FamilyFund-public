<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave-2 review (2026-05-14): `LineNotificationSettings::delayNotificationEnabled()`
 * was hard-coded `return true;` because the column did not exist. The other
 * settings methods read from columns added in
 * `2026_05_13_000006_add_notification_settings_to_account_credit_lines`.
 * Add the missing column with the same nullable + default-true shape so
 * existing rows opt in automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_credit_lines', function (Blueprint $table) {
            $table->boolean('delay_notification_enabled')
                ->nullable()
                ->default(true)
                ->after('delay_notification_grace_days');
        });
    }

    public function down(): void
    {
        Schema::table('account_credit_lines', function (Blueprint $table) {
            $table->dropColumn('delay_notification_enabled');
        });
    }
};
