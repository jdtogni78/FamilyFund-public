<?php

namespace Database\Seeders;

use App\Models\ConfigSetting;
use Illuminate\Database\Seeder;

class ConfigSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Email - General
            ['key' => 'mail.admin_email', 'value' => env('MAIL_ADMIN_ADDRESS', 'admin@example.com'), 'type' => 'email', 'category' => 'mail', 'description' => 'Admin notification email address'],
            ['key' => 'mail.from_address', 'value' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'), 'type' => 'email', 'category' => 'mail', 'description' => 'Default from email address'],
            ['key' => 'mail.from_name', 'value' => env('MAIL_FROM_NAME', 'Family Fund'), 'type' => 'string', 'category' => 'mail', 'description' => 'Default from name'],

            // Email - SMTP Settings
            ['key' => 'mail.smtp_host', 'value' => env('MAIL_HOST', 'smtp.mailgun.org'), 'type' => 'string', 'category' => 'mail', 'description' => 'SMTP server hostname'],
            ['key' => 'mail.smtp_port', 'value' => env('MAIL_PORT', '587'), 'type' => 'integer', 'category' => 'mail', 'description' => 'SMTP server port'],
            ['key' => 'mail.smtp_username', 'value' => env('MAIL_USERNAME', ''), 'type' => 'string', 'category' => 'mail', 'description' => 'SMTP username'],
            ['key' => 'mail.smtp_password', 'value' => '', 'type' => 'string', 'category' => 'mail', 'description' => 'SMTP password', 'is_sensitive' => true],
            ['key' => 'mail.smtp_encryption', 'value' => env('MAIL_ENCRYPTION', 'tls'), 'type' => 'string', 'category' => 'mail', 'description' => 'SMTP encryption (tls, ssl, or empty)'],
            ['key' => 'mail.mailer', 'value' => env('MAIL_MAILER', 'smtp'), 'type' => 'string', 'category' => 'mail', 'description' => 'Mail driver (smtp, sendmail, log)'],

            // Display
            ['key' => 'display.default_per_page', 'value' => '20', 'type' => 'integer', 'category' => 'display', 'description' => 'Default records per page'],
            ['key' => 'display.date_format', 'value' => 'M j, Y', 'type' => 'string', 'category' => 'display', 'description' => 'Date display format'],
            ['key' => 'display.currency_symbol', 'value' => '$', 'type' => 'string', 'category' => 'display', 'description' => 'Currency symbol'],

            // Storage & Retention
            ['key' => 'storage.email_retention_years', 'value' => '2', 'type' => 'integer', 'category' => 'storage', 'description' => 'Email log retention in years'],
            ['key' => 'storage.attachment_retention_years', 'value' => '2', 'type' => 'integer', 'category' => 'storage', 'description' => 'Attachment retention in years'],
            ['key' => 'storage.log_retention_days', 'value' => '90', 'type' => 'integer', 'category' => 'storage', 'description' => 'Application log retention in days'],

            // Reports
            ['key' => 'reports.quickchart_url', 'value' => 'http://quickchart:3400', 'type' => 'url', 'category' => 'reports', 'description' => 'QuickChart service URL'],

            // Security
            ['key' => 'security.session_timeout_minutes', 'value' => '120', 'type' => 'integer', 'category' => 'security', 'description' => 'Session timeout in minutes'],
            ['key' => 'security.2fa_required', 'value' => 'false', 'type' => 'boolean', 'category' => 'security', 'description' => 'Require 2FA for all users'],
            ['key' => 'security.login_attempts_limit', 'value' => '5', 'type' => 'integer', 'category' => 'security', 'description' => 'Max login attempts before lockout'],

            // Feature Flags
            ['key' => 'features.email_logging', 'value' => 'true', 'type' => 'boolean', 'category' => 'features', 'description' => 'Enable email logging'],
            ['key' => 'features.dark_mode', 'value' => 'true', 'type' => 'boolean', 'category' => 'features', 'description' => 'Enable dark mode toggle'],

            // System
            ['key' => 'system.app_name', 'value' => env('APP_NAME', 'Family Fund'), 'type' => 'string', 'category' => 'system', 'description' => 'Application name'],
            ['key' => 'system.maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'category' => 'system', 'description' => 'Enable maintenance mode'],
        ];

        foreach ($settings as $setting) {
            ConfigSetting::firstOrCreate(
                ['key' => $setting['key']],
                array_merge($setting, ['updated_by' => 'seeder'])
            );
        }
    }
}
