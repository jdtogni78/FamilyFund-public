<?php

namespace App\Providers;

use App\Listeners\LogQueueJobCompletion;
use App\Models\ConfigSetting;
use App\Models\TransactionExt;
use App\Observers\TransactionObserver;
use App\Services\CreditLine\Matching\Contracts\ScheduleAdvancer;
use App\Services\CreditLine\Repay\RepayService;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Knp\Snappy\Pdf;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Snappy PDF wrapper
        $this->app->bind('snappy.pdf.wrapper', function ($app) {
            $binary = config('snappy.pdf.binary', '/usr/local/bin/wkhtmltopdf');
            $options = config('snappy.pdf.options', []);

            $snappy = new Pdf($binary, $options);
            $snappy->setTimeout(config('snappy.pdf.timeout', 60));

            return new \App\Services\SnappyPdfWrapper($snappy);
        });

        // Credit-line: bind the ScheduleAdvancer contract (wave 1b → wave 1a).
        $this->app->bind(ScheduleAdvancer::class, RepayService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // `php artisan serve` only forwards a whitelist of env vars to the dev
        // server subprocess, so docker-compose.env.yml's per-nickname overrides
        // were being dropped over HTTP — non-`dev` stacks silently fell back to
        // .env's familyfund_dev. Pass the per-stack overrides + preview-build
        // identity (config/build.php) through so each worktree stack uses its
        // own DB and shows its branch/build banner.
        if (class_exists(ServeCommand::class)) {
            ServeCommand::$passthroughVariables = array_merge(
                ServeCommand::$passthroughVariables,
                ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'FF_NICKNAME', 'FF_BUILD_REF', 'FF_BUILD_LABEL']
            );
        }

        // Use Bootstrap 5 pagination styling
        Paginator::useBootstrapFive();

        // Set default password validation rules
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // Register queue job event subscriber
        Event::subscribe(LogQueueJobCompletion::class);

        // Credit-line: observe TransactionExt saves to drive detection / classification.
        // Laravel keys eloquent events on the concrete class; every prod path
        // (DrawService, RepayService, …) saves via TransactionExt, so the
        // listener must be registered on TransactionExt — not the base Transaction.
        TransactionExt::observe(TransactionObserver::class);

        // Note: LogSentEmail listener is auto-discovered by Laravel 11
        // based on the MessageSent type-hint in the handle() method

        // Decrypt mail password if encrypted version is set
        if ($encrypted = env('MAIL_PASSWORD_ENCRYPTED')) {
            try {
                Config::set('mail.mailers.smtp.password', Crypt::decrypt($encrypted));
            } catch (\Exception $e) {
                Log::warning('Failed to decrypt MAIL_PASSWORD_ENCRYPTED - using MAIL_PASSWORD instead: ' . $e->getMessage());
            }
        }

        // Apply mail settings from database (if available)
        $this->applyMailSettings();
    }

    /**
     * Apply mail settings from config_settings table
     */
    private function applyMailSettings(): void
    {
        try {
            // Only apply if table exists and has mail settings
            if (!Schema::hasTable('config_settings')) {
                return;
            }

            $mailSettings = ConfigSetting::where('key', 'like', 'mail.smtp_%')
                ->orWhere('key', 'mail.mailer')
                ->orWhere('key', 'like', 'mail.from_%')
                ->pluck('value', 'key')
                ->toArray();

            if (empty($mailSettings)) {
                return;
            }

            // Override Laravel mail config
            if (isset($mailSettings['mail.mailer'])) {
                Config::set('mail.default', $mailSettings['mail.mailer']);
            }
            if (isset($mailSettings['mail.smtp_host'])) {
                Config::set('mail.mailers.smtp.host', $mailSettings['mail.smtp_host']);
            }
            if (isset($mailSettings['mail.smtp_port'])) {
                Config::set('mail.mailers.smtp.port', (int) $mailSettings['mail.smtp_port']);
            }
            if (isset($mailSettings['mail.smtp_username'])) {
                Config::set('mail.mailers.smtp.username', $mailSettings['mail.smtp_username']);
            }
            if (isset($mailSettings['mail.smtp_password']) && !empty($mailSettings['mail.smtp_password'])) {
                Config::set('mail.mailers.smtp.password', $mailSettings['mail.smtp_password']);
            }
            if (isset($mailSettings['mail.smtp_encryption'])) {
                Config::set('mail.mailers.smtp.encryption', $mailSettings['mail.smtp_encryption'] ?: null);
            }
            if (isset($mailSettings['mail.from_address'])) {
                Config::set('mail.from.address', $mailSettings['mail.from_address']);
            }
            if (isset($mailSettings['mail.from_name'])) {
                Config::set('mail.from.name', $mailSettings['mail.from_name']);
            }

        } catch (\Exception $e) {
            // Silently fail - database might not be available yet
            Log::debug('Could not apply mail settings from database: ' . $e->getMessage());
        }
    }
}
