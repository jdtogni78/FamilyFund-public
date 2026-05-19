<?php

namespace App\Services\CreditLine\Settings;

use App\Models\AccountCreditLine;

/**
 * Notification settings for a single credit line.
 *
 * Phase 1: returns hard-coded defaults.
 *
 * Phase 2 TODO: Read from AccountCreditLine columns listed below (schema change
 * required — add these columns in a migration):
 *
 *   | Column                          | Type     | Default | Notes                                  |
 *   |---------------------------------|----------|---------|----------------------------------------|
 *   | reminder_enabled                | boolean  | true    | Toggle for pre-due reminders (UC-18)   |
 *   | reminder_lead_days              | integer  | 7       | Days before due_date to send reminder  |
 *   | delay_notification_enabled      | boolean  | true    | Toggle for late-payment emails (UC-19) |
 *   | delay_notification_grace_days   | integer  | 3       | Days after due_date before first alert |
 *   | delay_notification_repeat_days  | integer  | 14      | Days between repeat delay alerts       |
 *   | delay_notification_max          | integer  | 6       | Hard cap on repeat delay notifications |
 *   | transaction_email_enabled       | boolean  | true    | Toggle for received/detected emails (UC-34) |
 */
class LineNotificationSettings
{
    // Hard-coded defaults (Phase 1).
    public const DEFAULT_REMINDER_ENABLED               = true;
    public const DEFAULT_REMINDER_LEAD_DAYS             = 7;
    public const DEFAULT_DELAY_NOTIFICATION_ENABLED     = true;
    public const DEFAULT_DELAY_NOTIFICATION_GRACE_DAYS  = 3;
    public const DEFAULT_DELAY_NOTIFICATION_REPEAT_DAYS = 14;
    public const DEFAULT_DELAY_NOTIFICATION_MAX         = 6;
    public const DEFAULT_TRANSACTION_EMAIL_ENABLED      = true;
    public const DEFAULT_STATUS_UPDATE_ENABLED          = true;

    private AccountCreditLine $line;

    public function __construct(AccountCreditLine $line)
    {
        $this->line = $line;
    }

    public static function for(AccountCreditLine $line): self
    {
        return new self($line);
    }

    public function reminderEnabled(): bool
    {
        return (bool) ($this->line->reminder_enabled ?? self::DEFAULT_REMINDER_ENABLED);
    }

    public function reminderLeadDays(): int
    {
        return (int) ($this->line->reminder_lead_days ?? self::DEFAULT_REMINDER_LEAD_DAYS);
    }

    public function delayNotificationEnabled(): bool
    {
        // Wave-2 review (2026-05-14): wired to the new `delay_notification_enabled`
        // column (migration 2026_05_14_000001). Defaults to true when the column
        // is missing (older rows, or the migration hasn't run yet) so the
        // existing late-notification behaviour is preserved.
        return (bool) ($this->line->delay_notification_enabled ?? self::DEFAULT_DELAY_NOTIFICATION_ENABLED);
    }

    public function delayNotificationGraceDays(): int
    {
        return (int) ($this->line->delay_notification_grace_days ?? self::DEFAULT_DELAY_NOTIFICATION_GRACE_DAYS);
    }

    public function delayNotificationRepeatDays(): int
    {
        return (int) ($this->line->delay_notification_repeat_days ?? self::DEFAULT_DELAY_NOTIFICATION_REPEAT_DAYS);
    }

    public function delayNotificationMax(): int
    {
        return (int) ($this->line->delay_notification_max_repeats ?? self::DEFAULT_DELAY_NOTIFICATION_MAX);
    }

    public function transactionEmailEnabled(): bool
    {
        return (bool) ($this->line->transaction_email_enabled ?? self::DEFAULT_TRANSACTION_EMAIL_ENABLED);
    }

    public function mismatchAlertEnabled(): bool
    {
        return (bool) ($this->line->mismatch_alert_enabled ?? true);
    }

    /**
     * Whether this line is included in the periodic (quarterly) status-update
     * digest. Defaults to true when the column is missing (older rows / before
     * the Phase 2 schema change). See the column table in the class docblock.
     */
    public function statusUpdateEnabled(): bool
    {
        return (bool) ($this->line->status_update_enabled ?? self::DEFAULT_STATUS_UPDATE_ENABLED);
    }
}
