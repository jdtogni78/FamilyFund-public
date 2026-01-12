<?php

namespace App\Providers;

use App\Listeners\LogQueueJobCompletion;
use App\Listeners\LogSentEmail;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default password validation rules
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // Register queue job event subscriber
        Event::subscribe(LogQueueJobCompletion::class);

        // Log all sent emails to filesystem
        Event::listen(MessageSent::class, LogSentEmail::class);

        // Decrypt mail password if encrypted version is set
        if ($encrypted = env('MAIL_PASSWORD_ENCRYPTED')) {
            try {
                Config::set('mail.mailers.smtp.password', Crypt::decrypt($encrypted));
            } catch (\Exception $e) {
                \Log::warning('Failed to decrypt MAIL_PASSWORD_ENCRYPTED - using MAIL_PASSWORD instead: ' . $e->getMessage());
            }
        }
    }
}
